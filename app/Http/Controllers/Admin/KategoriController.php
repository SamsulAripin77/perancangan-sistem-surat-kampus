<?php

namespace App\Http\Controllers\Admin;

use App\Filters\KategoriFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\KategoriRequest;
use App\Models\KategoriSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD Master Kategori Surat (F12, UX_SPEC 2.C). Index DataTables + filter
 * reusable (§17); form modal; hapus dijaga guard "dipakai template" (ERD §6 FK
 * restrict) → nonaktifkan. Controller tipis.
 */
class KategoriController extends Controller
{
    public function index(Request $request, KategoriFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(KategoriSurat::query(), $request->only(['q', 'status']));

            return DataTables::eloquent($query)
                ->addColumn('dipakai', fn (KategoriSurat $k): string => (string) $k->templatesCount())
                ->editColumn('is_active', fn (KategoriSurat $k): string => view('admin.kategori.partials.status', ['kategori' => $k])->render())
                ->addColumn('aksi', fn (KategoriSurat $k): string => view('admin.kategori.partials.actions', ['kategori' => $k])->render())
                ->rawColumns(['is_active', 'aksi'])
                ->toJson();
        }

        return view('admin.kategori.index');
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        KategoriSurat::query()->create($request->validated());

        return redirect()->route('admin.kategori.index')->with('success', __('kategori.created'));
    }

    public function update(KategoriRequest $request, KategoriSurat $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()->route('admin.kategori.index')->with('success', __('kategori.updated'));
    }

    public function destroy(KategoriSurat $kategori): RedirectResponse
    {
        if ($kategori->isInUse()) {
            return redirect()->route('admin.kategori.index')->with('error', __('kategori.delete_blocked'));
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')->with('success', __('kategori.deleted'));
    }

    public function toggle(KategoriSurat $kategori): RedirectResponse
    {
        $kategori->update(['is_active' => ! $kategori->is_active]);

        return redirect()->route('admin.kategori.index')
            ->with('success', __($kategori->is_active ? 'kategori.activated' : 'kategori.deactivated'));
    }
}
