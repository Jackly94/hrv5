<?php

declare(strict_types=1);

namespace App\Shared\Biodata\Sections;

use App\Shared\EmployeeRepository;
use App\Shared\MediaHostFileService;
use App\Shared\ReferenceDataService;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AkaunRequestService
{
    private const TABLE = 'hronline.tbl_akaun_permohonan_tukar';

    /**
     * @var list<array<string, mixed>>
     */
    private const FIELDS = [
        ['name' => 'no_akaun', 'label' => 'No. Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'kod_bank', 'label' => 'Nama Bank', 'type' => 'select', 'options' => 'banks', 'required' => true],
        ['name' => 'alasan', 'label' => 'Alasan', 'type' => 'textarea', 'required' => true],
        ['name' => 'nama_pemilik', 'label' => 'Nama Pemilik Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'ic_pemilik', 'label' => 'IC/Paspot Pemilik Akaun', 'type' => 'text', 'required' => true],
        ['name' => 'attachment', 'label' => 'Lampiran Akaun', 'type' => 'file', 'required' => true],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private MediaHostFileService $fileService,
        private ReferenceDataService $referenceData,
        private EmployeeRepository $employees,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function overview(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'title' => 'Permohonan Penukaran Akaun Bank',
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'backUrl' => '/biodata/akaun/senarai-akaun',
            'addUrl' => '/biodata/akaun/mohon-tukar-akaun',
            'addLabel' => 'Mohon Baru',
            'info' => 'Permohonan yang dihantar pada bulan semasa hanya akan berkuatkuasa pada bulan berikutnya sekiranya diluluskan.',
            'columns' => [
                'ICNO/Paspot Kakitangan',
                'Nama Pemilik Akaun',
                'IC/Paspot Pemilik Akaun',
                'No. Akaun',
                'Nama Bank',
                'Tarikh Mohon',
                'Status',
            ],
            'rows' => array_map(fn (array $row): array => $this->requestRow($row), $this->requestRows($icno)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'key' => 'account-request',
            'title' => 'Akaun Bank Gaji Baharu',
            'pageTitle' => 'Permohonan Penukaran Akaun',
            'sectionTitle' => 'Maklumat Akaun Bank Gaji Baharu',
            'submitLabel' => 'Mohon',
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'backUrl' => '/biodata/akaun/permohonan-penukaran-akaun-gaji',
            'action' => '/biodata/akaun/mohon-tukar-akaun',
            'fields' => self::FIELDS,
            'options' => ['banks' => $this->referenceData->banks()],
            'values' => $this->defaultValues($icno, $staff),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, UploadedFileInterface|null> $files
     * @return array{success: bool, values: array<string, string>, errors: array<string, string>, id?: string}
     */
    public function save(string $icno, array $input, array $files): array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return ['success' => false, 'values' => $this->normalizeValues($input, []), 'errors' => ['general' => 'Rekod staf tidak dijumpai.']];
        }

        $values = $this->normalizeValues($input, $this->defaultValues($icno, $staff));
        $errors = $this->validate($values, $files);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $record = $this->recordFromValues($icno, $values);
        $record['kod_swift'] = $this->swiftCode((string) $record['kod_bank']);
        $record['application_dt'] = date('Y-m-d');

        $uploadErrors = $this->applyUpload($icno, $record, $files);
        if ($uploadErrors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $uploadErrors];
        }

        try {
            $savedId = $this->db->transaction(function () use ($record): string {
                $this->db->createCommand()->insert(self::TABLE, $record)->execute();

                return (string) $this->db->getLastInsertId();
            });
        } catch (\Throwable) {
            return [
                'success' => false,
                'values' => $values,
                'errors' => ['general' => 'Permohonan penukaran akaun tidak berjaya dihantar. Sila cuba lagi.'],
            ];
        }

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $savedId];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $id, string $icno): ?array
    {
        if ($id === '') {
            return null;
        }

        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $row = $this->requestRecord($id, $icno);
        if ($row === null) {
            return null;
        }

        return [
            'title' => 'Permohonan Penukaran Akaun',
            'name' => $this->titleCase($staff['CONm'] ?? null),
            'groupTitle' => 'Maklumat Akaun Bank',
            'updateUrl' => '',
            'fields' => [
                ['label' => 'No. Akaun Bank', 'value' => $this->value($row['no_akaun'] ?? null, '-')],
                ['label' => 'Nama Bank', 'value' => $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-')],
                ['label' => 'Nama Pemilik Akaun Bank', 'value' => $this->value($row['nama_pemilik'] ?? null, '-')],
                ['label' => 'IC/Paspot Pemilik Akaun Bank', 'value' => $this->value($row['ic_pemilik'] ?? null, '-')],
                ['label' => 'Alasan', 'value' => $this->value($row['alasan'] ?? null, '-')],
                ['label' => 'Tarikh Mohon', 'value' => $this->formatDate($row['application_dt'] ?? null)],
                ['label' => 'Status', 'value' => $this->statusLabel($row)],
                ...$this->attachments([['label' => 'Lampiran Akaun', 'hash' => $row['attachment'] ?? null]]),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requestRows(string $icno): array
    {
        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT application.*, bank.bank_desc
                FROM hronline.tbl_akaun_permohonan_tukar application
                LEFT JOIN hronline.tbl_bank_main bank ON bank.bank_code = application.kod_bank
                WHERE application.icno = :icno
                ORDER BY application.application_dt ASC, application.id ASC
                SQL,
                [':icno' => $icno],
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function requestRow(array $row): array
    {
        $id = $this->value($row['id'] ?? null, '');

        return [
            'id' => $id,
            'cells' => [
                $this->value($row['icno'] ?? null, '-'),
                $this->value($row['nama_pemilik'] ?? null, '-'),
                $this->value($row['ic_pemilik'] ?? null, '-'),
                $this->value($row['no_akaun'] ?? null, '-'),
                $this->value($row['bank_desc'] ?? $row['kod_bank'] ?? null, '-'),
                $this->formatDate($row['application_dt'] ?? null),
                $this->statusLabel($row),
            ],
            'viewUrl' => $id === '' ? '' : '/biodata/akaun/lihat-acc?id=' . rawurlencode($id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestRecord(string $id, string $icno): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT application.*, bank.bank_desc
                FROM hronline.tbl_akaun_permohonan_tukar application
                LEFT JOIN hronline.tbl_bank_main bank ON bank.bank_code = application.kod_bank
                WHERE application.id = :id AND application.icno = :icno
                LIMIT 1
                SQL,
                [':id' => $id, ':icno' => $icno],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $staff
     * @return array<string, string>
     */
    private function defaultValues(string $icno, array $staff): array
    {
        return [
            'no_akaun' => '',
            'kod_bank' => '',
            'alasan' => '',
            'nama_pemilik' => $this->value($staff['CONm'] ?? null, ''),
            'ic_pemilik' => $icno,
            'attachment' => '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $defaults
     * @return array<string, string>
     */
    private function normalizeValues(array $input, array $defaults): array
    {
        $values = $defaults;
        foreach (self::FIELDS as $field) {
            $name = (string) $field['name'];
            if (($field['type'] ?? '') === 'file') {
                $values[$name] = $defaults[$name] ?? '';
                continue;
            }

            $values[$name] = $this->formValue($input[$name] ?? ($defaults[$name] ?? ''));
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, UploadedFileInterface|null> $files
     * @return array<string, string>
     */
    private function validate(array $values, array $files): array
    {
        $errors = [];
        foreach (self::FIELDS as $field) {
            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            if (($field['required'] ?? false) && $type !== 'file' && ($values[$name] ?? '') === '') {
                $errors[$name] = $field['label'] . ' diperlukan.';
            }

            if ($type === 'file') {
                $fileError = $this->validateUploadedFile($files[$name] ?? null, (bool) ($field['required'] ?? false));
                if ($fileError !== null) {
                    $errors[$name] = $fileError;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, string> $values
     * @return array<string, mixed>
     */
    private function recordFromValues(string $icno, array $values): array
    {
        return [
            'icno' => $icno,
            'nama_pemilik' => $values['nama_pemilik'] ?? '',
            'ic_pemilik' => $values['ic_pemilik'] ?? '',
            'no_akaun' => $values['no_akaun'] ?? '',
            'kod_bank' => $values['kod_bank'] ?? '',
            'alasan' => $values['alasan'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, UploadedFileInterface|null> $files
     * @return array<string, string>
     */
    private function applyUpload(string $icno, array &$record, array $files): array
    {
        $file = $files['attachment'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['attachment' => 'Lampiran Akaun diperlukan.'];
        }

        $upload = $this->fileService->uploadDocument($file, 'Maklumat Rekod Kakitangan/akaun', $icno);
        if ($upload['success'] !== true) {
            return ['attachment' => $upload['message'] ?? 'Muat naik dokumen gagal.'];
        }

        $record['attachment'] = (string) $upload['hash'];

        return [];
    }

    private function validateUploadedFile(?UploadedFileInterface $file, bool $required): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required ? 'Lampiran Akaun diperlukan.' : null;
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Dokumen tidak berjaya dimuat naik.';
        }

        if (($file->getSize() ?? 0) > 6 * 1024 * 1024) {
            return 'Saiz dokumen tidak boleh melebihi 6.0 MB.';
        }

        $filename = strtolower((string) $file->getClientFilename());
        if (!preg_match('/\.(pdf|jpg|jpeg|png)$/', $filename)) {
            return 'Sila muat naik fail PDF, JPG, JPEG atau PNG.';
        }

        return null;
    }

    private function swiftCode(string $bankCode): string
    {
        if ($bankCode === '') {
            return '';
        }

        try {
            $swiftCode = $this->db->createCommand(
                'SELECT swift_code FROM hronline.tbl_bank_main WHERE bank_code = :bankCode LIMIT 1',
                [':bankCode' => $bankCode],
            )->queryScalar();
        } catch (\Throwable) {
            return '';
        }

        return $this->value($swiftCode, '');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function statusLabel(array $row): string
    {
        $verifyStatus = $this->value($row['verify_status'] ?? null, '');
        $approvalStatus = $this->value($row['approval_status'] ?? null, '');

        if ($verifyStatus === '1') {
            return match ($approvalStatus) {
                '1' => 'Diluluskan',
                '2' => 'Ditolak',
                default => 'Menunggu Kelulusan',
            };
        }

        if ($verifyStatus === '2') {
            return 'Dikembalikan';
        }

        return 'Menunggu Pengesahan';
    }

    /**
     * @param list<array{label: string, hash: mixed}> $files
     * @return list<array{label: string, value: string, url: string, kind: string}>
     */
    private function attachments(array $files): array
    {
        $attachments = [];
        foreach ($files as $file) {
            $hash = $this->value($file['hash'] ?? null, '');
            $url = $this->fileService->viewUrl($hash);
            if ($url === '') {
                continue;
            }

            $attachments[] = [
                'label' => $this->value($file['label'] ?? null, 'Fail'),
                'value' => 'Lihat Fail',
                'url' => $url,
                'kind' => 'file',
            ];
        }

        return $attachments;
    }

    private function formatDate(mixed $value): string
    {
        $value = $this->value($value, '');
        if ($value === '') {
            return '-';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('d/m/Y', $timestamp);
    }

    private function formValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function titleCase(mixed $value): string
    {
        $value = $this->value($value, '');

        return $value === '' ? 'Tiada Rekod' : ucwords(strtolower($value));
    }

    private function value(mixed $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
