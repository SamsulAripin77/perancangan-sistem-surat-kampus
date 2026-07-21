<?php

namespace App\Http\Controllers\Admin;

use App\Filters\PlaceholderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceholderRequest;
use App\Models\PlaceholderDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD Master Kamus Placeholder (F13, UX_SPEC 2.B) — khusus Super Admin (gating
 * route). Perubahan berdampak semua template → konfirmasi di UI. Controller
 * tipis; filter di PlaceholderFilter.
 */
class KamusController extends Controller
{
    public function index(Request $request, PlaceholderFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(PlaceholderDefinition::query(), $request->only(['q', 'kelompok']));

            return DataTables::eloquent($query)
                ->editColumn('is_overridable', fn (PlaceholderDefinition $p): string => view('admin.kamus.partials.overridable', ['item' => $p])->render())
                ->addColumn('aksi', fn (PlaceholderDefinition $p): string => view('admin.kamus.partials.actions', ['item' => $p])->render())
                ->rawColumns(['is_overridable', 'aksi'])
                ->toJson();
        }

        return view('admin.kamus.index');
    }

    public function store(PlaceholderRequest $request): RedirectResponse
    {
        PlaceholderDefinition::query()->create($request->validated());

        return redirect()->route('admin.kamus.index')->with('success', __('kamus.created'));
    }

    public function update(PlaceholderRequest $request, PlaceholderDefinition $kamus): RedirectResponse
    {
        $kamus->update($request->validated());

        return redirect()->route('admin.kamus.index')->with('success', __('kamus.updated'));
    }

    public function destroy(PlaceholderDefinition $kamus): RedirectResponse
    {
        $kamus->delete();

        return redirect()->route('admin.kamus.index')->with('success', __('kamus.deleted'));
    }
}
