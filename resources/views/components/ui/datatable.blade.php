@props(['columns' => [], 'ajax' => null, 'id' => null])
{{--
    Markup + kontrak data-* untuk DataTables server-side (Yajra). Inisialisasi
    JS aktual (`.js-datatable` → `.DataTable({...})`) ditambahkan di M0-T7,
    membaca `data-url` & `data-columns` (JSON: [{data, label}, ...]).
--}}
<table @if ($id) id="{{ $id }}" @endif
    {{ $attributes->merge([
        'class' => 'table table-sm table-bordered table-striped table-hover app-table js-datatable',
    ]) }}
    @if ($ajax) data-url="{{ $ajax }}" @endif
    data-columns="{{ json_encode($columns) }}">
    <thead class="table-light">
        <tr>
            @foreach ($columns as $column)
                <th>{{ $column['label'] ?? $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody></tbody>
</table>
