<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pejabat\SavePejabat;
use App\Filters\PejabatFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\PejabatRequest;
use App\Models\Pejabat;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD Pejabat penandatangan (F2, UX_SPEC 1.C.3): multi-unit (n—n) + TTD master
 * opsional (disk private). Index DataTables server-side + filter reusable (§17);
 * controller tipis — simpan didelegasikan ke SavePejabat, filter ke PejabatFilter.
 */
class PejabatController extends Controller
{
    public function index(Request $request, PejabatFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(
                Pejabat::query()->with(['units', 'media']),
                $request->only(['q', 'unit', 'status']),
            );

            return DataTables::eloquent($query)
                ->addColumn('units_list', fn (Pejabat $p): string => $p->units->pluck('nama')->implode(', ') ?: '—')
                ->addColumn('ttd', fn (Pejabat $p): string => view('admin.pejabat.partials.ttd', ['pejabat' => $p])->render())
                ->editColumn('is_active', fn (Pejabat $p): string => view('admin.pejabat.partials.status', ['pejabat' => $p])->render())
                ->addColumn('aksi', fn (Pejabat $p): string => view('admin.pejabat.partials.actions', ['pejabat' => $p])->render())
                ->rawColumns(['ttd', 'is_active', 'aksi'])
                ->toJson();
        }

        return view('admin.pejabat.index', [
            'units' => Unit::query()->where('is_active', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(PejabatRequest $request, SavePejabat $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.pejabat.index')->with('success', __('pejabat.created'));
    }

    public function update(PejabatRequest $request, Pejabat $pejabat, SavePejabat $action): RedirectResponse
    {
        $action->handle($request->validated(), $pejabat);

        return redirect()->route('admin.pejabat.index')->with('success', __('pejabat.updated'));
    }

    public function destroy(Pejabat $pejabat): RedirectResponse
    {
        if ($pejabat->isInUse()) {
            return redirect()->route('admin.pejabat.index')->with('error', __('pejabat.delete_blocked'));
        }

        $pejabat->units()->detach();
        $pejabat->delete();

        return redirect()->route('admin.pejabat.index')->with('success', __('pejabat.deleted'));
    }

    public function toggle(Pejabat $pejabat): RedirectResponse
    {
        $pejabat->update(['is_active' => ! $pejabat->is_active]);

        return redirect()->route('admin.pejabat.index')
            ->with('success', __($pejabat->is_active ? 'pejabat.activated' : 'pejabat.deactivated'));
    }
}
