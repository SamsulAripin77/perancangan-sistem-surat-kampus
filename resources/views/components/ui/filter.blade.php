@props(['table', 'search' => true])
{{--
    Bar filter reusable (ARCHITECTURE §17). `table` = selector tabel target
    (mis. "#unitTable"); input filter (class `js-filter-input`) dibaca hook
    `js-filter` di app.js lalu di-inject ke ajax DataTables + reload saat berubah.
    Filter kontekstual tambahan (status/kategori/tanggal) diletakkan via slot.
--}}
<div class="app-filter card app-card mb-2 js-filter" data-table="{{ $table }}">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            @if ($search)
                <div class="col-sm-4">
                    <input type="text" name="q"
                        class="form-control form-control-sm js-filter-input" data-filter-debounce
                        placeholder="{{ __('common.cari') }}">
                </div>
            @endif
            {{ $slot }}
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-secondary js-filter-reset">
                    {{ __('common.reset') }}
                </button>
            </div>
        </div>
    </div>
</div>
