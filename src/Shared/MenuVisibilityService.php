<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class MenuVisibilityService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private AuthorizationService $authorization,
    ) {}

    /**
     * @return array{visible: bool, supported: bool, reason: string}
     */
    public function evaluate(string $expression, string $icno): array
    {
        $expression = trim($expression);
        if ($expression === '') {
            return ['visible' => true, 'supported' => true, 'reason' => 'empty'];
        }

        $parts = preg_split('/\|\|/', $expression) ?: [];
        $unsupported = [];
        foreach ($parts as $part) {
            $result = $this->evaluateAndPart($part, $icno);
            if ($result['supported'] && $result['visible']) {
                return ['visible' => true, 'supported' => true, 'reason' => $result['reason']];
            }

            if (!$result['supported']) {
                $unsupported[] = $result['reason'];
            }
        }

        if ($unsupported !== []) {
            return ['visible' => false, 'supported' => false, 'reason' => implode('; ', array_unique($unsupported))];
        }

        return ['visible' => false, 'supported' => true, 'reason' => 'supported-false'];
    }

    /**
     * @return array{visible: bool, supported: bool, reason: string}
     */
    private function evaluateAndPart(string $expression, string $icno): array
    {
        $conditions = preg_split('/&&/', $expression) ?: [];
        $reasons = [];
        foreach ($conditions as $condition) {
            $condition = trim($condition);
            if ($condition === '' || $condition === '(' || $condition === ')') {
                continue;
            }

            $result = $this->evaluateCondition($condition, $icno);
            if (!$result['supported']) {
                return $result;
            }

            $reasons[] = $result['reason'];
            if (!$result['visible']) {
                return ['visible' => false, 'supported' => true, 'reason' => implode('+', $reasons)];
            }
        }

        return ['visible' => true, 'supported' => true, 'reason' => implode('+', $reasons)];
    }

    /**
     * @return array{visible: bool, supported: bool, reason: string}
     */
    private function evaluateCondition(string $condition, string $icno): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($condition, " \t\n\r\0\x0B()")) ?? '';

        if ($this->isStaffExistsExpression($normalized)) {
            return ['visible' => $this->matchesStaffProfileRule($normalized, $icno), 'supported' => true, 'reason' => 'staff-profile'];
        }

        if (str_contains($normalized, 'UtilitiesFunc::Internal')) {
            return ['visible' => $this->isInternalStaff($icno), 'supported' => true, 'reason' => 'internal-staff'];
        }

        if (str_contains($normalized, 'TblUserAccess::find()')) {
            return ['visible' => $this->hasUserAccess($icno, $this->accessValues($normalized)), 'supported' => true, 'reason' => 'user-access'];
        }

        if (str_contains($normalized, 'Yii::$app->MP->isKetuaProgram()')) {
            return ['visible' => $this->authorization->isKetuaProgram($icno), 'supported' => true, 'reason' => 'ketua-program'];
        }

        if (str_contains($normalized, 'TblStaffKeselamatan::find()')) {
            return ['visible' => $this->isSecurityStaff($icno), 'supported' => true, 'reason' => 'security-staff'];
        }

        if (str_contains($normalized, 'TblAkses::find()')) {
            return ['visible' => $this->hasSecurityAccess($icno), 'supported' => true, 'reason' => 'security-access'];
        }

        if (str_contains($normalized, 'Department::find()')) {
            return ['visible' => $this->isDepartmentLeader($icno), 'supported' => true, 'reason' => 'department-leader'];
        }

        if (str_contains($normalized, 'accessLevel') || str_contains($normalized, 'accessSecondLevel')) {
            return ['visible' => $this->matchesAccessLevelRule($normalized, $icno), 'supported' => true, 'reason' => 'access-level'];
        }

        if (str_contains($normalized, 'in_array(Yii::$app->user->getId()') || str_contains($normalized, 'Yii::$app->user->identity->ICNO ==')) {
            return ['visible' => $this->matchesIcnoAllowlist($normalized, $icno), 'supported' => true, 'reason' => 'icno-allowlist'];
        }

        return ['visible' => false, 'supported' => false, 'reason' => 'unsupported: ' . $this->shorten($normalized)];
    }

    private function isStaffExistsExpression(string $expression): bool
    {
        return str_contains($expression, 'Tblprcobiodata::find()')
            && str_contains($expression, 'ICNO')
            && str_contains($expression, 'exists');
    }

    private function matchesStaffProfileRule(string $expression, string $icno): bool
    {
        $row = $this->db->createCommand(
            'SELECT Status, statLantikan FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();
        if (!is_array($row)) {
            return false;
        }

        if (str_contains($expression, "['!=','Status',6]") && (string) ($row['Status'] ?? '') === '6') {
            return false;
        }

        if (preg_match("/\\['IN','statLantikan',\\[([^\\]]+)\\]\\]/", $expression, $match) === 1) {
            return in_array((string) ($row['statLantikan'] ?? ''), $this->quotedValues($match[1]), true);
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function accessValues(string $expression): array
    {
        if (preg_match("/'access'\s*=>\s*\[([^\]]+)\]/", $expression, $match) === 1) {
            return $this->quotedValues($match[1]);
        }

        if (preg_match("/'access'\s*=>\s*'?([0-9]+)'?/", $expression, $match) === 1) {
            return [$match[1]];
        }

        return [];
    }

    /**
     * @param list<string> $accessValues
     */
    private function hasUserAccess(string $icno, array $accessValues): bool
    {
        $params = [':icno' => $icno];
        $where = 'icno = :icno';
        if ($accessValues !== []) {
            $tokens = [];
            foreach ($accessValues as $index => $value) {
                $token = ':access' . $index;
                $tokens[] = $token;
                $params[$token] = $value;
            }

            $where .= ' AND access IN (' . implode(', ', $tokens) . ')';
        }

        return (bool) $this->db->createCommand(
            "SELECT 1 FROM system_core.tbl_user_access WHERE {$where} LIMIT 1",
            $params,
        )->queryScalar();
    }

    private function isInternalStaff(string $icno): bool
    {
        $row = $this->employees->byIcno($icno);

        return $row !== null && (string) ($row['Status'] ?? '') !== '6';
    }

    private function isSecurityStaff(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM keselamatan.tbl_staff_keselamatan
            WHERE staff_icno = :icno
              AND isActive = 1
              AND COALESCE(isExcluded, 0) = 0
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryScalar();
    }

    private function hasSecurityAccess(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM keselamatan.tbl_akses WHERE icno = :icno AND isActive = 1 LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();
    }

    private function isDepartmentLeader(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hronline.department WHERE isActive = 1 AND (chief = :icno OR pp = :icno) LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();
    }

    private function matchesAccessLevelRule(string $expression, string $icno): bool
    {
        $access = $this->db->createCommand(
            'SELECT accessLevel, accessSecondLevel FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();
        if (!is_array($access)) {
            return false;
        }

        $level = (string) ($access['accessLevel'] ?? '');
        $secondLevel = (string) ($access['accessSecondLevel'] ?? '');

        if (preg_match_all("/accessLevel\s*==\s*'([^']+)'/", $expression, $matches) > 0) {
            foreach ($matches[1] as $expected) {
                if ($level === $expected && $this->secondLevelMatches($expression, $secondLevel)) {
                    return true;
                }
            }

            return false;
        }

        return $this->secondLevelMatches($expression, $secondLevel);
    }

    private function secondLevelMatches(string $expression, string $secondLevel): bool
    {
        if (!str_contains($expression, 'accessSecondLevel')) {
            return true;
        }

        if (preg_match_all("/\['IN',\s*Yii::\\\$app->user->identity->accessSecondLevel,\s*\[([^\]]+)\]\s*\]/", $expression, $matches) < 1) {
            return false;
        }

        foreach ($matches[1] as $list) {
            if (in_array($secondLevel, $this->quotedValues($list), true)) {
                return true;
            }
        }

        return false;
    }

    private function matchesIcnoAllowlist(string $expression, string $icno): bool
    {
        if (preg_match_all("/'([0-9]{12})'/", $expression, $matches) < 1) {
            return false;
        }

        return in_array($icno, $matches[1], true);
    }

    /**
     * @return list<string>
     */
    private function quotedValues(string $valueList): array
    {
        if (preg_match_all("/'([^']+)'|([0-9]+)/", $valueList, $matches, PREG_SET_ORDER) < 1) {
            return [];
        }

        $values = [];
        foreach ($matches as $match) {
            $values[] = ($match[1] ?? '') !== '' ? $match[1] : $match[2];
        }

        return $values;
    }

    private function shorten(string $expression): string
    {
        return strlen($expression) <= 140 ? $expression : substr($expression, 0, 137) . '...';
    }
}
