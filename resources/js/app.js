import './bootstrap';

// jQuery harus global sebelum DataTables/Select2 diimpor — keduanya
// menempel ke $.fn saat modul dimuat (§10 catatan jQuery).
import $ from 'jquery';
window.$ = window.jQuery = $;

import 'bootstrap';
import 'admin-lte';

import 'datatables.net-bs5';

// select2's CJS build exports a factory that must be called with jQuery to
// register `$.fn.select2`; a bare import never invokes it under Vite interop.
import select2 from 'select2';
select2();

import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

FilePond.registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
    FilePondPluginImagePreview,
);

import Swal from 'sweetalert2';
window.Swal = Swal;

// Titik inisialisasi global hook `js-*` (§11.2, §11.4) — satu tempat.

$('.js-datatable').each(function () {
    const $table = $(this);
    const columns = ($table.data('columns') || []).map((column) => ({ data: column.data }));

    $table.DataTable({
        serverSide: true,
        ajax: $table.data('url'),
        columns,
        pageLength: 20,
    });
});

$('.js-select2').select2({
    theme: 'bootstrap-5',
    width: '100%',
});

document.querySelectorAll('.js-upload').forEach((el) => {
    FilePond.create(el, {
        acceptedFileTypes: el.dataset.accept ? el.dataset.accept.split(',') : undefined,
        maxFileSize: el.dataset.maxSize || undefined,
    });
});

document.querySelectorAll('.js-flash').forEach((el) => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
        icon: el.dataset.type,
        title: el.dataset.msg,
    });
});

document.querySelectorAll('.js-confirm').forEach((el) => {
    el.addEventListener('click', async (e) => {
        e.preventDefault();

        const result = await Swal.fire({
            icon: 'warning',
            text: el.dataset.confirm,
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
        });

        if (!result.isConfirmed) {
            return;
        }

        const form = el.closest('form');
        if (form) {
            form.submit();
        } else if (el.dataset.url) {
            window.location.href = el.dataset.url;
        }
    });
});
