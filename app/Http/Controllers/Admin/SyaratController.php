<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Syarat\SaveRefSyarat;
use App\Filters\SyaratFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyaratRequest;
use App\Models\RefSyaratSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD Master Persyaratan (F4, UX_SPEC 2.D). File contoh disk private diunduh
 * lewat route ber-gating (§9). Hapus dijaga guard "dipakai template". Controller
 * tipis: simpan didelegasikan ke SaveRefSyarat, filter ke SyaratFilter.
 */
class SyaratController extends Controller
{
    public function index(Request $request, SyaratFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(RefSyaratSurat::query()->with('media'), $request->only(['q']));

            return DataTables::eloquent($query)
                ->addColumn('dipakai', fn (RefSyaratSurat $s): string => (string) $s->templatesCount())
                ->addColumn('file', fn (RefSyaratSurat $s): string => view('admin.syarat.partials.file', ['syarat' => $s])->render())
                ->addColumn('aksi', fn (RefSyaratSurat $s): string => view('admin.syarat.partials.actions', ['syarat' => $s])->render())
                ->rawColumns(['file', 'aksi'])
                ->toJson();
        }

        return view('admin.syarat.index');
    }

    public function store(SyaratRequest $request, SaveRefSyarat $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.persyaratan.index')->with('success', __('syarat.created'));
    }

    public function update(SyaratRequest $request, RefSyaratSurat $persyaratan, SaveRefSyarat $action): RedirectResponse
    {
        $action->handle($request->validated(), $persyaratan);

        return redirect()->route('admin.persyaratan.index')->with('success', __('syarat.updated'));
    }

    public function destroy(RefSyaratSurat $persyaratan): RedirectResponse
    {
        if ($persyaratan->isInUse()) {
            return redirect()->route('admin.persyaratan.index')->with('error', __('syarat.delete_blocked'));
        }

        $persyaratan->delete();

        return redirect()->route('admin.persyaratan.index')->with('success', __('syarat.deleted'));
    }

    /**
     * Unduh file contoh (disk private) — gated middleware admin (§9).
     */
    public function download(RefSyaratSurat $persyaratan): BinaryFileResponse
    {
        $media = $persyaratan->getFirstMedia('template');

        abort_if($media === null, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
