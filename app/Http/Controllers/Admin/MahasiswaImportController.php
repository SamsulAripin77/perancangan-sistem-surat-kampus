<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MahasiswaImportRequest;
use App\Services\MahasiswaImportService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import Mahasiswa SIAKAD (F1, UX_SPEC 2.A.3) — khusus Super Admin (gating
 * route). Alur dua langkah: unggah → pratinjau (validasi per baris) → commit.
 * File pratinjau disimpan sementara (token) lalu diurai ulang saat commit agar
 * data baris tidak dipercaya dari klien. Logika parsing/validasi/impor di
 * MahasiswaImportService.
 */
class MahasiswaImportController extends Controller
{
    public function __construct(
        private readonly MahasiswaImportService $import,
        private readonly MediaService $media,
    ) {}

    public function form(): View
    {
        return view('admin.user.import.form');
    }

    /**
     * Unduh template CSV kolom `nim,nama,email,prodi` + satu baris contoh.
     */
    public function template(): StreamedResponse
    {
        $rows = [
            MahasiswaImportService::COLUMNS,
            ['20210001', 'Budi Santoso', 'budi@example.ac.id', 'Informatika'],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'template-import-mahasiswa.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(MahasiswaImportRequest $request): View|RedirectResponse
    {
        $token = $this->media->storeTemporary($request->file('file'));
        $path = $this->pathFor($token);

        if ($path === null) {
            return redirect()->route('admin.user.import.form')->with('error', __('import.expired'));
        }

        $analysis = $this->import->analyze($this->import->rows($path));

        return view('admin.user.import.preview', [
            'token' => $token,
            'rows' => $analysis['rows'],
            'valid' => $analysis['valid'],
            'skipped' => $analysis['skipped'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $path = $this->pathFor($token);

        if ($path === null) {
            return redirect()->route('admin.user.import.form')->with('error', __('import.expired'));
        }

        $result = $this->import->import($path);
        $this->media->deleteTemporary($token);

        return redirect()->route('admin.user.index')->with('success', __('import.summary', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ]));
    }

    /**
     * Path absolut file sementara pada disk private, atau null bila token tidak
     * valid / file sudah tidak ada.
     */
    private function pathFor(string $token): ?string
    {
        $relative = $this->media->temporaryFilePath($token);

        return $relative !== null ? Storage::disk('private')->path($relative) : null;
    }
}
