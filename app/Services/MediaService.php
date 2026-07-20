<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * SSOT aturan upload (ARCHITECTURE §9). Semua fitur meng-attach file lewat
 * service ini — tidak ada logika upload duplikat di fitur manapun.
 *
 * Alur FilePond (temporary upload tanpa MediaLibrary Pro):
 *   1. FilePond `process` → storeTemporary() menyimpan ke folder sementara
 *      (disk private) & mengembalikan token (uuid).
 *   2. FilePond `revert` → deleteTemporary($token) menghapusnya.
 *   3. Saat form submit, Action fitur memanggil attachFromTemporary($model,
 *      $token, $collection) untuk memindahkan file ke media collection.
 */
class MediaService
{
    /** Folder root upload sementara di disk private. */
    public const TEMP_DIRECTORY = 'tmp-uploads';

    private const TEMP_DISK = 'private';

    /**
     * Attach file yang diunggah langsung ke media collection sebuah model.
     */
    public function attach(HasMedia $model, UploadedFile $file, string $collection): Media
    {
        return $model->addMedia($file)
            ->sanitizingFileName($this->sanitizeFileName(...))
            ->toMediaCollection($collection);
    }

    /**
     * Simpan file unggahan ke folder sementara, kembalikan token (uuid) yang
     * dipakai FilePond sebagai server id.
     */
    public function storeTemporary(UploadedFile $file): string
    {
        $token = (string) Str::uuid();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            .'.'.$file->getClientOriginalExtension();

        $file->storeAs(self::TEMP_DIRECTORY.'/'.$token, $safeName, ['disk' => self::TEMP_DISK]);

        return $token;
    }

    /**
     * Hapus folder sementara sebuah token (dipakai FilePond `revert`).
     */
    public function deleteTemporary(string $token): void
    {
        if (! $this->isValidToken($token)) {
            return;
        }

        Storage::disk(self::TEMP_DISK)->deleteDirectory(self::TEMP_DIRECTORY.'/'.$token);
    }

    /**
     * Attach file dari folder sementara ke media collection, lalu bersihkan
     * folder sementaranya. Mengembalikan null bila token tidak valid/kosong.
     */
    public function attachFromTemporary(HasMedia $model, string $token, string $collection): ?Media
    {
        $path = $this->temporaryFilePath($token);

        if ($path === null) {
            return null;
        }

        $media = $model->addMedia(Storage::disk(self::TEMP_DISK)->path($path))
            ->sanitizingFileName($this->sanitizeFileName(...))
            ->toMediaCollection($collection);

        $this->deleteTemporary($token);

        return $media;
    }

    /**
     * Path relatif (di disk private) file dalam folder sementara token, atau
     * null bila token invalid / tidak ada file.
     */
    public function temporaryFilePath(string $token): ?string
    {
        if (! $this->isValidToken($token)) {
            return null;
        }

        $files = Storage::disk(self::TEMP_DISK)->files(self::TEMP_DIRECTORY.'/'.$token);

        return $files[0] ?? null;
    }

    /**
     * Token wajib UUID — mencegah path traversal pada folder sementara.
     */
    private function isValidToken(string $token): bool
    {
        return Str::isUuid($token);
    }

    /**
     * Nama file aman: slug pada nama (anti guessing/traversal) sambil menjaga
     * ekstensi tetap utuh.
     */
    private function sanitizeFileName(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }
}
