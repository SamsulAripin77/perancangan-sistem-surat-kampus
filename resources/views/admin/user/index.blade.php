@extends('layouts.app', ['title' => __('user.title')])

@php
    use Database\Seeders\RolePermissionSeeder;
@endphp

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#userModal"
                data-action="{{ route('admin.user.store') }}"
                data-method="POST"
                data-title="{{ __('user.create_title') }}">
                <i class="fas fa-plus"></i> {{ __('user.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#userTable">
            <div class="col-sm-2">
                <select name="role" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('user.role_all') }}</option>
                    @foreach (RolePermissionSeeder::ROLES as $role)
                        <option value="{{ $role }}">{{ __("user.role_{$role}") }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <select name="unit" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('user.unit_all') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('user.status_all') }}</option>
                    <option value="1">{{ __('user.active') }}</option>
                    <option value="0">{{ __('user.inactive') }}</option>
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="userTable" :ajax="route('admin.user.index')" :columns="[
            ['data' => 'name', 'label' => __('user.nama')],
            ['data' => 'email', 'label' => __('user.email')],
            ['data' => 'role', 'label' => __('user.role'), 'orderable' => false, 'searchable' => false],
            ['data' => 'nim', 'label' => __('user.nim'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_active', 'label' => __('user.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit (field mahasiswa muncul kondisional via js-role-toggle). --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.user.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.user.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('user.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <x-form.input name="name" :label="__('user.nama')" required />
                    <x-form.input name="email" type="email" :label="__('user.email')" required />
                    <x-form.input name="password" type="password" :label="__('user.password')"
                        autocomplete="new-password" />
                    <div class="form-text mb-3">{{ __('user.password_hint') }}</div>

                    <div class="mb-3 app-form-group">
                        <label class="form-label d-block">{{ __('user.role') }} <span class="text-danger">*</span></label>
                        @foreach (RolePermissionSeeder::ROLES as $i => $role)
                            <div class="form-check form-check-inline">
                                <input type="radio" name="role" id="role_{{ $role }}" value="{{ $role }}"
                                    class="form-check-input" @checked($role === 'mahasiswa')>
                                <label for="role_{{ $role }}" class="form-check-label">{{ __("user.role_{$role}") }}</label>
                            </div>
                        @endforeach
                        @error('role')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 app-form-group">
                        <label for="unit_id" class="form-label">{{ __('user.unit') }}</label>
                        <select name="unit_id" id="unit_id" class="form-select form-select-sm js-select2"
                            data-dropdown-parent="#userModal">
                            <option value="">{{ __('user.no_unit') }}</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="js-mahasiswa-fields border-top pt-3">
                        <x-form.input name="nim" :label="__('user.nim')" />
                        <x-form.input name="prodi" :label="__('user.prodi')" />
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" name="is_active" id="user_is_active" value="1"
                            class="form-check-input" checked>
                        <label for="user_is_active" class="form-check-label">{{ __('user.active') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        {{ __('common.tutup') }}
                    </button>
                    <x-ui.button type="submit" variant="primary" icon="save">{{ __('common.simpan') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            window.__reopenModal = {
                selector: '#userModal',
                action: @json(old('form_action', route('admin.user.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    name: @json(old('name')),
                    email: @json(old('email')),
                    role: @json(old('role')),
                    unit_id: @json(old('unit_id')),
                    nim: @json(old('nim')),
                    prodi: @json(old('prodi')),
                    is_active: @json((bool) old('is_active')),
                },
            };
        </script>
    @endif
@endsection
