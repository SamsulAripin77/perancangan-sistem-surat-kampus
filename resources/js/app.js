import './bootstrap';

// jQuery harus global sebelum DataTables/Select2 diimpor — keduanya
// menempel ke $.fn saat modul dimuat (§10 catatan jQuery).
import $ from 'jquery';
window.$ = window.jQuery = $;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
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
    const columns = ($table.data('columns') || []).map((column) => ({
        data: column.data,
        name: column.data,
        orderable: column.orderable !== false,
        searchable: column.searchable !== false,
    }));

    // Bar filter reusable (§17) yang menargetkan tabel ini via data-table.
    const tableId = this.id ? `#${this.id}` : null;
    const $filter = tableId ? $(`.js-filter[data-table="${tableId}"]`) : $();

    const table = $table.DataTable({
        serverSide: true,
        ajax: {
            url: $table.data('url'),
            // Sisipkan nilai filter ke setiap request server-side.
            data: (params) => {
                $filter.find('.js-filter-input').each(function () {
                    params[this.name] = this.value;
                });
            },
        },
        columns,
        pageLength: 20,
        orderMulti: false,
        language: { url: undefined },
    });

    // Search box: reload ter-debounce; dropdown: reload saat berubah.
    let debounce;
    $filter.on('input', '.js-filter-input[data-filter-debounce]', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => table.ajax.reload(), 300);
    });
    $filter.on('change', '.js-filter-input:not([data-filter-debounce])', () => table.ajax.reload());
    $filter.on('click', '.js-filter-reset', () => {
        $filter.find('.js-filter-input').val('');
        table.ajax.reload();
    });
});

$('.js-select2').each(function () {
    const $el = $(this);
    const parent = $el.data('dropdown-parent');

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        // Di dalam modal, dropdown harus dianak-pinning ke modal agar bisa fokus.
        dropdownParent: parent ? $(parent) : undefined,
    });
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

document.querySelectorAll('.js-upload').forEach((el) => {
    FilePond.create(el, {
        acceptedFileTypes: el.dataset.accept ? el.dataset.accept.split(',') : undefined,
        maxFileSize: el.dataset.maxSize || undefined,
        // Endpoint generik MediaUploadController (§9): process simpan sementara,
        // revert batal. Token yang dikembalikan jadi nilai submit field ini.
        server: {
            process: {
                url: '/upload',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            },
            revert: {
                url: '/upload',
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            },
        },
    });
});

// Modal create/edit reusable (§11): tombol `js-modal-open` mengisi form modal
// dari data-* (action, method, title, fields JSON) lalu menampilkannya.
function populateModalForm($form, { action, method, fields, title }) {
    $form[0].reset();
    $form.attr('action', action);
    $form.find('[name="_method"]').val(method || 'POST');
    $form.find('[name="form_action"]').val(action);

    Object.entries(fields || {}).forEach(([name, value]) => {
        const $input = $form.find(`[name="${name}"], [name="${name}[]"]`);
        if ($input.attr('type') === 'checkbox') {
            $input.prop('checked', Boolean(value));
        } else if ($input.attr('type') === 'radio') {
            $input.filter(`[value="${value}"]`).prop('checked', true).trigger('change');
        } else if ($input.is('select[multiple]')) {
            // Select2 tags: set nilai lalu picu change agar UI tersinkron.
            $input.val(value || []).trigger('change');
        } else {
            $input.val(value ?? '').trigger('change');
        }
    });

    if (title) {
        $form.find('.js-modal-title').text(title);
    }
}

$(document).on('click', '.js-modal-open', function () {
    const $modal = $(this.dataset.modal);
    populateModalForm($modal.find('form'), {
        action: this.dataset.action,
        method: this.dataset.method,
        title: this.dataset.title,
        fields: this.dataset.fields ? JSON.parse(this.dataset.fields) : {},
    });
    // Sinkronkan field kondisional (mis. profil mahasiswa) sesuai role terpilih.
    $modal.find('[name="role"]:checked').trigger('change');
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
});

// Field profil mahasiswa hanya tampil bila role = mahasiswa (UX_SPEC 2.A.2).
function syncMahasiswaFields($form) {
    const role = $form.find('[name="role"]:checked').val();
    $form.find('.js-mahasiswa-fields').toggle(role === 'mahasiswa');
}
$(document).on('change', '[name="role"]', function () {
    syncMahasiswaFields($(this).closest('form'));
});

// Buka ulang modal saat validasi gagal (nilai lama dari server). Halaman
// menyetel window.__reopenModal = { selector, action, method, fields }.
if (window.__reopenModal) {
    const $modal = $(window.__reopenModal.selector);
    if ($modal.length) {
        populateModalForm($modal.find('form'), window.__reopenModal);
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }
}

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
