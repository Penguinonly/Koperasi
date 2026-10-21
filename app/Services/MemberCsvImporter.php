<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MemberCsvImporter
{
    public function import(string $path): int
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Member CSV not found: {$path}");
        }

        $headers = fgetcsv($handle);
        $imported = 0;

        DB::transaction(function () use ($handle, $headers, &$imported) {
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_pad($row, count($headers), null);
                $data = array_combine($headers, $row);
                $memberId = trim((string) ($data['ID Anggota'] ?? ''));
                $name = trim((string) ($data['Nama'] ?? ''));

                if ($memberId === '' || $name === '') {
                    continue;
                }

                $resignedAt = $this->parseDate($data['Tanggal Berhenti/Dipecat'] ?? null);

                Customer::query()->updateOrCreate(
                    ['member_code' => (string) ((int) $memberId)],
                    [
                        'name' => $name,
                        'gender' => $this->nullable($data['Jenis Kelamin'] ?? null),
                        'occupation' => $this->nullable($data['Mata Pencaharian'] ?? null),
                        'address' => $this->nullable($data['Alamat Tempat Tinggal'] ?? null),
                        'resignation_requested_at' => $this->parseDate($data['Tanggal Minta Berhenti'] ?? null),
                        'resigned_at' => $resignedAt,
                        'exit_reason' => $this->nullable($data['Alasan Berhenti/Dipecat'] ?? null),
                        'status' => $resignedAt ? 'INACTIVE' : 'ACTIVE',
                    ],
                );

                $imported++;
            }
        });

        fclose($handle);

        return $imported;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || str_contains($value, '...')) {
            return null;
        }

        $value = str_replace(['-', ':'], '/', $value);
        $parts = preg_split('/\//', $value);

        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !ctype_digit($parts[2])) {
            return null;
        }

        [$day, $month, $year] = array_map('intval', $parts);
        $year = $year < 100 ? 2000 + $year : $year;

        try {
            return Carbon::createSafe($year, $month, $day)?->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}