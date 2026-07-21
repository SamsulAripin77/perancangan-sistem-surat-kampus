<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Setting\UpdateSystemSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateKonfigurasiRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pengaturan Sistem — form bergrup key-value (UX_SPEC 1.C.1, F2). Controller
 * tipis: baca setting untuk render, delegasikan penyimpanan ke Action.
 */
class KonfigurasiController extends Controller
{
    public function edit(SettingService $settings): View
    {
        return view('admin.konfigurasi.edit', [
            'grouped' => Setting::query()->orderBy('id')->get()->groupBy('group'),
            'settings' => $settings,
        ]);
    }

    public function update(UpdateKonfigurasiRequest $request, UpdateSystemSettings $action): RedirectResponse
    {
        $data = $request->validated();

        $action->handle($data, $data['logo_token'] ?? null);

        return redirect()->route('admin.konfigurasi.edit')
            ->with('success', __('konfigurasi.saved'));
    }
}
