<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TemplateStatus;
use App\Filters\TemplateFilter;
use App\Http\Controllers\Controller;
use App\Models\KategoriSurat;
use App\Models\Template;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Daftar Template Surat (F3, UX_SPEC 3.A). Index read-only dengan DataTables
 * server-side dan filter reusable (§17). Flow create/edit/delete menyusul task
 * M2 berikutnya.
 */
class TemplateController extends Controller
{
    public function index(Request $request, TemplateFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(
                Template::query()
                    ->with(['kategori:id,nama', 'units:id,nama'])
                    ->latest(),
                $request->only(['q', 'kategori_id', 'unit_id', 'status'])
            );

            return DataTables::eloquent($query)
                ->addColumn('kategori', fn (Template $template): string => $template->kategori->nama)
                ->addColumn('unit', function (Template $template): string {
                    $units = $template->units->pluck('nama')->join(', ');

                    return $units !== '' ? $units : '—';
                })
                ->editColumn('tipe_pemohon', fn (Template $template): string => __('template.tipe_pemohon_values.'.$template->tipePemohonValue()))
                ->editColumn('is_permohonan_mandiri', fn (Template $template): string => view('admin.template.partials.mandiri', ['template' => $template])->render())
                ->editColumn('status', fn (Template $template): string => view('admin.template.partials.status', ['template' => $template])->render())
                ->addColumn('aksi', fn (Template $template): string => view('admin.template.partials.actions', ['template' => $template])->render())
                ->rawColumns(['is_permohonan_mandiri', 'status', 'aksi'])
                ->toJson();
        }

        return view('admin.template.index', [
            'kategoriOptions' => KategoriSurat::query()->orderBy('nama')->get(['id', 'nama']),
            'unitOptions' => Unit::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => TemplateStatus::cases(),
        ]);
    }
}
