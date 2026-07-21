<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Import Mahasiswa dari file SIAKAD (M1-T8, UX_SPEC 2.A.3). Membaca `.xlsx/.csv`
 * via openspout (D-007), memvalidasi tiap baris, dan menandai status
 * (valid/invalid/duplicate) untuk pratinjau sebelum commit. Password TIDAK
 * diimpor — sistem generate acak + `must_change_password=true` (login pertama
 * memaksa ganti, M1-T2). Duplikat (`email`/`nim` sudah ada, atau ganda di file)
 * di-skip; user existing tidak disentuh (D-001: tanpa `fakultas`).
 */
class MahasiswaImportService
{
    /** Kolom wajib pada file import (urutan bebas, header case-insensitive). */
    public const COLUMNS = ['nim', 'nama', 'email', 'prodi'];

    /**
     * Baca file jadi list baris asosiatif berkunci COLUMNS. Baris pertama =
     * header (pemetaan kolom). Baris kosong dilewati.
     *
     * @return list<array{line:int, nim:string, nama:string, email:string, prodi:string}>
     */
    public function rows(string $absolutePath): array
    {
        $reader = $this->readerFor($absolutePath);
        $reader->open($absolutePath);

        $rows = [];
        $map = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                $cells = array_map(
                    static fn ($value): string => trim((string) $value),
                    $row->toArray(),
                );

                if ($map === null) {
                    $map = $this->headerMap($cells);

                    continue;
                }

                if ($cells === [] || implode('', $cells) === '') {
                    continue;
                }

                $rows[] = [
                    'line' => $index,
                    'nim' => $this->cell($cells, $map, 'nim'),
                    'nama' => $this->cell($cells, $map, 'nama'),
                    'email' => $this->cell($cells, $map, 'email'),
                    'prodi' => $this->cell($cells, $map, 'prodi'),
                ];
            }

            break; // hanya sheet pertama
        }

        $reader->close();

        return $rows;
    }

    /**
     * Analisis baris untuk pratinjau: tandai status per baris + ringkasan.
     * Status: `valid` (akan diimpor), `invalid` (gagal validasi), `duplicate`
     * (email/nim sudah ada di DB atau ganda dalam file — di-skip).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array{line:int, nim:string, nama:string, email:string, prodi:string, status:string, message:string}>, valid:int, skipped:int}
     */
    public function analyze(array $rows): array
    {
        $seenEmail = [];
        $seenNim = [];
        $analyzed = [];
        $valid = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $status = 'valid';
            $message = '';

            $validator = Validator::make($row, [
                'nim' => ['required', 'string', 'max:20'],
                'nama' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:150'],
                'prodi' => ['required', 'string', 'max:100'],
            ]);

            $email = strtolower((string) ($row['email'] ?? ''));
            $nim = (string) ($row['nim'] ?? '');

            if ($validator->fails()) {
                $status = 'invalid';
                $message = (string) $validator->errors()->first();
            } elseif (
                isset($seenEmail[$email]) || isset($seenNim[$nim])
                || User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()
                || Mahasiswa::query()->where('nim', $nim)->exists()
            ) {
                $status = 'duplicate';
                $message = __('import.dup_reason');
            }

            $seenEmail[$email] = true;
            $seenNim[$nim] = true;

            if ($status === 'valid') {
                $valid++;
            } else {
                $skipped++;
            }

            $analyzed[] = [...$row, 'status' => $status, 'message' => $message];
        }

        return ['rows' => $analyzed, 'valid' => $valid, 'skipped' => $skipped];
    }

    /**
     * Commit import: buat `users`(role mahasiswa, password acak,
     * must_change_password) + `mahasiswa` untuk baris berstatus `valid`; sisanya
     * dilewati. Idempoten terhadap duplikat karena analyze mengecek ulang DB.
     *
     * @return array{imported:int, skipped:int}
     */
    public function import(string $absolutePath): array
    {
        $analysis = $this->analyze($this->rows($absolutePath));

        $imported = 0;
        foreach ($analysis['rows'] as $row) {
            if ($row['status'] !== 'valid') {
                continue;
            }

            DB::transaction(function () use ($row): void {
                $user = User::query()->create([
                    'name' => $row['nama'],
                    'email' => $row['email'],
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $user->assignRole('mahasiswa');

                $user->mahasiswa()->create([
                    'nim' => $row['nim'],
                    'nama' => $row['nama'],
                    'prodi' => $row['prodi'],
                    'is_active' => true,
                ]);
            });

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $analysis['skipped']];
    }

    private function readerFor(string $path): ReaderInterface
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'xlsx'
            ? new XlsxReader
            : new CsvReader;
    }

    /**
     * Peta nama header (lowercase) → indeks kolom.
     *
     * @param  list<string>  $header
     * @return array<string, int>
     */
    private function headerMap(array $header): array
    {
        $map = [];
        foreach ($header as $index => $name) {
            $map[strtolower(trim($name))] = $index;
        }

        return $map;
    }

    /**
     * @param  list<string>  $cells
     * @param  array<string, int>  $map
     */
    private function cell(array $cells, array $map, string $column): string
    {
        $index = $map[$column] ?? null;

        return $index !== null ? ($cells[$index] ?? '') : '';
    }
}
