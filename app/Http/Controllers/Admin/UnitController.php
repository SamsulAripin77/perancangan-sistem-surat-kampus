<?php

namespace App\Http\Controllers\Admin;

use App\Filters\UnitFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD Unit penerbit (F2, UX_SPEC 1.C.2). Index DataTables server-side (Yajra)
 * + filter reusable (§17); form via modal; hapus dijaga guard "sedang dipakai".
 * Controller tipis — logika filter di UnitFilter, guard hapus di model.
 */
class UnitController extends Controller
{
    public function index(Request $request, UnitFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(Unit::query()->with('parent'), $request->only(['q', 'status']));

            return DataTables::eloquent($query)
                ->addColumn('parent_nama', fn (Unit $unit): string => optional($unit->parent)->nama ?? '—')
                ->editColumn('is_active', fn (Unit $unit): string => view('admin.unit.partials.status', ['unit' => $unit])->render())
                ->addColumn('aksi', fn (Unit $unit): string => view('admin.unit.partials.actions', ['unit' => $unit])->render())
                ->rawColumns(['is_active', 'aksi'])
                ->toJson();
        }

        return view('admin.unit.index', [
            'parents' => Unit::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::query()->create($request->validated());

        return redirect()->route('admin.unit.index')->with('success', __('unit.created'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('admin.unit.index')->with('success', __('unit.updated'));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->isInUse()) {
            return redirect()->route('admin.unit.index')->with('error', __('unit.delete_blocked'));
        }

        $unit->delete();

        return redirect()->route('admin.unit.index')->with('success', __('unit.deleted'));
    }

    public function toggle(Unit $unit): RedirectResponse
    {
        $unit->update(['is_active' => ! $unit->is_active]);

        return redirect()->route('admin.unit.index')
            ->with('success', __($unit->is_active ? 'unit.activated' : 'unit.deactivated'));
    }
}
