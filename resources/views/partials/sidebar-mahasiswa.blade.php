<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ url('/') }}" class="brand-link">
            <span class="brand-text fw-light">{{ config('app.name') }}</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ Route::has('mahasiswa.beranda') ? route('mahasiswa.beranda') : '#' }}"
                        class="nav-link {{ request()->routeIs('mahasiswa.beranda') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-house"></i>
                        <p>{{ __('common.beranda') }}</p>
                    </a>
                </li>
                {{--
                    Menu Ajukan Surat, Dokumen Saya, Riwayat Permohonan, Profil
                    ditambahkan seiring route-nya dibuat (M3).
                --}}
            </ul>
        </nav>
    </div>
</aside>
