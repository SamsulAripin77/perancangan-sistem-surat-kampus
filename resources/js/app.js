import './bootstrap';

// jQuery harus global sebelum DataTables/Select2 diimpor — keduanya
// menempel ke $.fn saat modul dimuat (§10 catatan jQuery).
import $ from 'jquery';
window.$ = window.jQuery = $;

import 'bootstrap';
import 'admin-lte';

import 'datatables.net-bs5';
import 'select2';

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

/*
 * Titik inisialisasi global hook `js-*` (§11.2: DataTables, Select2,
 * FilePond `js-upload`, SweetAlert `js-flash`/`js-confirm`) ditambahkan
 * di M0-T7, di file ini.
 */
