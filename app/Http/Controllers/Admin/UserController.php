<?php

namespace App\Http\Controllers\Admin;

use App\Actions\User\SaveUser;
use App\Filters\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * CRUD User + role + profil mahasiswa kondisional (F1, UX_SPEC 2.A). Khusus
 * Super Admin (gating route). Tanpa hard delete (D-005) — hanya toggle
 * `is_active`. Controller tipis: simpan didelegasikan ke SaveUser.
 */
class UserController extends Controller
{
    public function index(Request $request, UserFilter $filter): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $filter->apply(
                User::query()->with(['roles', 'mahasiswa']),
                $request->only(['q', 'role', 'unit', 'status']),
            );

            return DataTables::eloquent($query)
                ->addColumn('role', fn (User $u): string => $u->roles->pluck('name')->map(fn ($r) => __("user.role_{$r}"))->implode(', ') ?: '—')
                ->addColumn('nim', fn (User $u): string => optional($u->mahasiswa)->nim ?? '—')
                ->editColumn('is_active', fn (User $u): string => view('admin.user.partials.status', ['user' => $u])->render())
                ->addColumn('aksi', fn (User $u): string => view('admin.user.partials.actions', ['user' => $u])->render())
                ->rawColumns(['is_active', 'aksi'])
                ->toJson();
        }

        return view('admin.user.index', [
            'units' => Unit::query()->where('is_active', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(UserRequest $request, SaveUser $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.user.index')->with('success', __('user.created'));
    }

    public function update(UserRequest $request, User $user, SaveUser $action): RedirectResponse
    {
        $action->handle($request->validated(), $user);

        return redirect()->route('admin.user.index')->with('success', __('user.updated'));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        // Cegah super admin mengunci dirinya sendiri.
        if ($user->is($request->user())) {
            return redirect()->route('admin.user.index')->with('error', __('user.self_toggle_blocked'));
        }

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('admin.user.index')
            ->with('success', __($user->is_active ? 'user.activated' : 'user.deactivated'));
    }
}
