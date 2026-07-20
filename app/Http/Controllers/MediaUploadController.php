<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

/**
 * Controller AJAX generik untuk upload FilePond (ARCHITECTURE §9). Menangani
 * endpoint `process` (simpan sementara) & `revert` (batal) — attach ke media
 * collection sebuah model dilakukan Action fitur saat form submit via
 * MediaService::attachFromTemporary().
 */
class MediaUploadController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * FilePond `process`: simpan file ke folder sementara, kembalikan token
     * (server id) sebagai teks polos.
     */
    public function process(Request $request): Response
    {
        $file = collect($request->allFiles())->flatten()->first();

        abort_unless($file instanceof UploadedFile && $file->isValid(), 422, 'File tidak valid.');

        $token = $this->media->storeTemporary($file);

        return response($token, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * FilePond `revert`: hapus folder sementara sesuai token di body request.
     */
    public function revert(Request $request): Response
    {
        $this->media->deleteTemporary(trim($request->getContent()));

        return response()->noContent();
    }
}
