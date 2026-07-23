<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Template\SaveTemplate;
use App\Enums\TemplateStatus;
use App\Enums\TipePemohon;
use App\Filters\TemplateFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Models\KategoriSurat;
use App\Models\Template;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Template Surat (F3). M2-T3 menambahkan create/store tahap awal dan handoff
 * read-only ke route edit; scan placeholder dan hub lengkap menyusul M2-T4/T5.
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

    public function create(): View
    {
        return view('admin.template.create', [
            'kategoriOptions' => KategoriSurat::query()->orderBy('nama')->pluck('nama', 'id'),
            'unitOptions' => Unit::query()->orderBy('nama')->pluck('nama', 'id'),
            'tipePemohonOptions' => collect(TipePemohon::cases())
                ->mapWithKeys(fn (TipePemohon $tipe): array => [$tipe->value => __('template.tipe_pemohon_values.'.$tipe->value)]),
        ]);
    }

    public function store(StoreTemplateRequest $request, SaveTemplate $action): RedirectResponse
    {
        /** @var int $userId */
        $userId = $request->user()->id;
        $template = $action->handle($request->validated(), $userId);

        return redirect()->route('admin.template.edit', $template)
            ->with('success', __('template.created'));
    }

    public function edit(Template $template): View
    {
        $template->load(['kategori:id,nama', 'units:id,nama', 'media']);

        return view('admin.template.edit', ['template' => $template]);
    }
}
