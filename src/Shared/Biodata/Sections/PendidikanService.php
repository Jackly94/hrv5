<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

final readonly class PendidikanService
{
    public function __construct(private BiodataSectionOverviewSupport $support) {}

    public function overview(string $icno): ?array
    {
        return $this->support->overview($icno, [
            'key' => 'pendidikan',
            'title' => 'Pendidikan',
            'subtitle' => 'Rekod pendidikan kakitangan',
            'backUrl' => '/biodata/userview',
            'addUrl' => '/biodata/pendidikan/tambahpendidikan',
            'addLabel' => 'Tambah Pendidikan',
            'presentation' => 'tabs',
        ], $this->educationGroups($icno));
    }

    public function detail(string $id, string $icno): ?array
    {
        $overview = $this->overview($icno);

        return $overview === null ? null : $this->support->detail($overview, $id, 'education');
    }

    public function withActionQuery(array $overview, array $query): array
    {
        return $this->support->withActionQuery($overview, $query);
    }

    private function educationGroups(string $icno): array
    {
        $groups = [
            ['title' => 'Sarjana', 'ranks' => [1, 2, 3]],
            ['title' => 'Diploma/Setaraf', 'ranks' => [4]],
            ['title' => 'Sijil Kemahiran', 'ranks' => [5]],
            ['title' => 'SPM/Setaraf', 'ranks' => [6]],
            ['title' => 'PMR/PT3/Setaraf', 'ranks' => [7]],
            ['title' => 'Lain-lain Pendidikan', 'ranks' => [8, 9, 10]],
        ];

        return array_map(fn (array $group): array => [
            'title' => $group['title'],
            'variant' => 'education',
            'columns' => ['Institut', 'Tahap Pendidikan', 'Major', 'Nama Sijil (Malay)', 'Tarikh Dianugerahkan', 'Status Pengiktirafan'],
            'rows' => $this->educationRows($icno, $group['ranks']),
        ], $groups);
    }

    private function educationRows(string $icno, array $ranks): array
    {
        $rankList = implode(',', array_map('intval', $ranks));
        $rows = $this->support->queryAll(
            <<<SQL
            SELECT edu.id, inst.InstNm, level.HighestEduLevel, major.MajorMinor,
                   edu.EduCertTitle, edu.ConfermentDt, edu.AcrtdEduAch, edu.filename
            FROM hronline.tblpreduach edu
            LEFT JOIN hronline.educationallevel level ON level.HighestEduLevelCd = edu.HighestEduLevelCd
            LEFT JOIN hronline.institution inst ON inst.InstCd = edu.InstCd
            LEFT JOIN hronline.majorminor major ON major.MajorMinorCd = edu.MajorCd
            WHERE edu.ICNO = :icno AND level.HighestEduLevelRank IN ({$rankList})
            ORDER BY edu.ConfermentDt DESC, edu.id DESC
            SQL,
            [':icno' => $icno],
        );

        return array_map(fn (array $row): array => $this->support->row(
            $this->support->value($row['id'] ?? null, ''),
            [
                $this->support->value($row['InstNm'] ?? null, '-'),
                $this->support->value($row['HighestEduLevel'] ?? null, '-'),
                $this->support->value($row['MajorMinor'] ?? null, 'Tidak Berkaitan'),
                $this->support->value($row['EduCertTitle'] ?? null, '-'),
                $this->support->formatDate($row['ConfermentDt'] ?? null),
                ((string) ($row['AcrtdEduAch'] ?? '') === '1') ? 'Diiktiraf' : 'Tidak Berkaitan',
            ],
            '/biodata/pendidikan/lihatpendidikan?id=',
            '/biodata/pendidikan/update?id=',
            '/biodata/pendidikan/delete?id=',
            $this->support->attachments([
                ['label' => 'Fail', 'hash' => $row['filename'] ?? null],
            ]),
        ), $rows);
    }
}