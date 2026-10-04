/**
 * New Scoring Credit System — Global Application JS
 * Phase 5: Global Init, AJAX Helpers, SweetAlert Config, DataTables & Select2 Defaults
 *
 * Dependencies: jQuery 3.7+, Bootstrap 5, DataTables 2, Select2 4.1, SweetAlert2 11
 */

(function ($) {
    'use strict';

    /* ============================================================
       1. CSRF HELPER
       ============================================================ */
    var App = window.App = window.App || {};

    /**
     * Get current CSRF token from meta tag or cookie.
     * CodeIgniter 4 sets CSRF in cookie: csrf_cookie_name
     */
    App.getCsrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content');
        var match = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    };

    App.getCsrfName = function () {
        var meta = document.querySelector('meta[name="csrf-name"]');
        return meta ? meta.getAttribute('content') : 'csrf_test_name';
    };

    App.csrfData = function () {
        var data = {};
        data[App.getCsrfName()] = App.getCsrfToken();
        return data;
    };

    /* ============================================================
       2. SWEETALERT2 CONFIGURATION (Indonesian)
       ============================================================ */

    /**
     * Toast notification (top-right, auto-dismiss)
     */
    App.toast = function (opts) {
        var defaults = {
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: function (toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        };
        return Swal.fire($.extend({}, defaults, opts));
    };

    App.toastSuccess = function (message, title) {
        return App.toast({ icon: 'success', title: title || 'Berhasil!', text: message });
    };

    App.toastError = function (message, title) {
        return App.toast({ icon: 'error', title: title || 'Gagal!', text: message });
    };

    App.toastWarning = function (message, title) {
        return App.toast({ icon: 'warning', title: title || 'Perhatian', text: message });
    };

    App.toastInfo = function (message, title) {
        return App.toast({ icon: 'info', title: title || 'Informasi', text: message });
    };

    /**
     * Full alert modal
     */
    App.alert = function (opts) {
        var defaults = {
            customClass: { confirmButton: 'btn btn-primary px-4' },
            buttonsStyling: false
        };
        return Swal.fire($.extend({}, defaults, opts));
    };

    App.alertSuccess = function (title, message, detail) {
        var html = detail ? '<div class="p-2 bg-light rounded mt-2 small"><i class="fa-solid fa-file-lines me-1"></i>' + detail + '</div>' : '';
        return App.alert({
            icon: 'success',
            title: title || 'Berhasil!',
            html: (message || '') + html,
            confirmButtonText: 'OK <i class="fa-solid fa-arrow-right ms-1"></i>'
        });
    };

    App.alertError = function (title, message) {
        return App.alert({
            icon: 'error',
            title: title || 'Gagal!',
            text: message || 'Terjadi kesalahan. Silakan coba lagi.',
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-danger px-4' }
        });
    };

    App.alertWarning = function (title, message) {
        return App.alert({
            icon: 'warning',
            title: title || 'Perhatian!',
            text: message,
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-warning px-4' }
        });
    };

    App.alertInfo = function (title, message) {
        return App.alert({
            icon: 'info',
            title: title || 'Informasi',
            text: message,
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-primary px-4' }
        });
    };

    /**
     * Confirmation dialog
     */
    App.confirm = function (opts) {
        var defaults = {
            icon: 'question',
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin melanjutkan?',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Lanjutkan',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            },
            buttonsStyling: false
        };
        return Swal.fire($.extend({}, defaults, opts));
    };

    App.confirmSave = function (opts) {
        return App.confirm($.extend({
            title: 'Simpan Data',
            text: 'Apakah Anda yakin ingin menyimpan data ini?',
            confirmButtonText: '<i class="fa-solid fa-floppy-disk me-1"></i> Ya, Simpan'
        }, opts));
    };

    App.confirmDelete = function (opts) {
        return App.confirm($.extend({
            icon: 'warning',
            title: 'Hapus Data',
            text: 'Data yang dihapus tidak dapat dikembalikan. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus',
            customClass: {
                confirmButton: 'btn btn-danger px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            }
        }, opts));
    };

    App.confirmApprove = function (opts) {
        return App.confirm($.extend({
            icon: 'question',
            title: 'Setujui Pengajuan',
            text: 'Apakah Anda yakin ingin menyetujui pengajuan ini?',
            confirmButtonText: '<i class="fa-solid fa-circle-check me-1"></i> Ya, Setujui',
            customClass: {
                confirmButton: 'btn btn-success px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            }
        }, opts));
    };

    App.confirmReject = function (opts) {
        return App.confirm($.extend({
            icon: 'warning',
            title: 'Tolak Pengajuan',
            text: 'Apakah Anda yakin ingin menolak pengajuan ini?',
            confirmButtonText: '<i class="fa-solid fa-circle-xmark me-1"></i> Ya, Tolak',
            customClass: {
                confirmButton: 'btn btn-danger px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            }
        }, opts));
    };

    App.confirmReturn = function (opts) {
        return App.confirm($.extend({
            icon: 'warning',
            title: 'Kembalikan Pengajuan',
            text: 'Pengajuan akan dikembalikan ke pengaju untuk perbaikan.',
            confirmButtonText: '<i class="fa-solid fa-rotate-left me-1"></i> Ya, Kembalikan',
            customClass: {
                confirmButton: 'btn btn-warning px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            }
        }, opts));
    };

    App.confirmDeactivate = function (opts) {
        return App.confirm($.extend({
            icon: 'warning',
            title: 'Nonaktifkan',
            text: 'Data ini akan dinonaktifkan. Lanjutkan?',
            confirmButtonText: '<i class="fa-solid fa-ban me-1"></i> Ya, Nonaktifkan',
            customClass: {
                confirmButton: 'btn btn-warning px-4',
                cancelButton:  'btn btn-light px-4 me-2'
            }
        }, opts));
    };

    /* ============================================================
       3. BUTTON LOADING STATE
       ============================================================ */
    App.btnLoading = function ($btn, loadingText) {
        var original = $btn.html();
        $btn.data('original-html', original)
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + (loadingText || 'Memproses...'));
        return original;
    };

    App.btnReset = function ($btn) {
        var original = $btn.data('original-html');
        if (original) {
            $btn.prop('disabled', false).html(original);
        }
    };

    /* ============================================================
       4. JQUERY AJAX HELPER
       ============================================================ */
    /**
     * Standard AJAX request with loading state and error handling.
     *
     * Usage:
     *   App.ajax({
     *     url: '/api/endpoint',
     *     method: 'POST',
     *     data: { key: value },
     *     $btn: $(this),          // optional — button to disable during request
     *     loadingText: 'Menyimpan...',
     *     onSuccess: function(data) { ... },
     *     onError: function(data, status) { ... },
     *     onValidationError: function(errors) { ... },
     *   });
     */
    App.ajax = function (opts) {
        var csrfData   = App.csrfData();
        var data       = $.extend({}, csrfData, opts.data || {});
        var $btn       = opts.$btn || null;
        var loadingText = opts.loadingText || 'Memproses...';

        if ($btn) App.btnLoading($btn, loadingText);

        return $.ajax({
            url:         opts.url,
            method:      opts.method || 'POST',
            data:        data,
            dataType:    'json',
            headers:     { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .done(function (res) {
            // Update CSRF token if returned
            if (res && res.csrf) {
                var name = App.getCsrfName();
                document.cookie = name + '=' + encodeURIComponent(res.csrf) + '; path=/';
            }

            if (res && res.ok === false) {
                // Validation errors
                if (res.errors && opts.onValidationError) {
                    opts.onValidationError(res.errors);
                } else if (opts.onError) {
                    opts.onError(res, 'server');
                } else {
                    App.alertError(null, res.message || 'Terjadi kesalahan. Silakan coba lagi.');
                }
                return;
            }

            if (opts.onSuccess) {
                opts.onSuccess(res);
            }
        })
        .fail(function (xhr) {
            var res = null;
            try { res = JSON.parse(xhr.responseText); } catch(e) {}

            if (xhr.status === 401 || xhr.status === 403) {
                App.alertError('Akses Ditolak', 'Sesi Anda mungkin telah berakhir. Silakan muat ulang halaman.').then(function () {
                    window.location.reload();
                });
                return;
            }

            if (opts.onError) {
                opts.onError(res, xhr.status);
            } else {
                App.alertError(null, (res && res.message) || 'Terjadi kesalahan jaringan. Silakan coba lagi.');
            }
        })
        .always(function () {
            if ($btn) App.btnReset($btn);
        });
    };

    /* ============================================================
       5. DATATABLES DEFAULT CONFIGURATION
       ============================================================ */
    App.dtDefaults = {
        language: {
            processing:     '<div class="d-flex align-items-center gap-2 py-2"><span class="spinner-border spinner-border-sm text-primary"></span> <span>Memuat data...</span></div>',
            search:         '',
            searchPlaceholder: 'Cari...',
            lengthMenu:     'Tampilkan _MENU_ data',
            info:           'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty:      'Tidak ada data',
            infoFiltered:   '(dari total _MAX_ data)',
            zeroRecords:    '<div class="empty-state py-4"><i class="fa-regular fa-folder-open fa-2x text-muted mb-2"></i><p class="mb-0">Tidak ada data yang sesuai.</p></div>',
            emptyTable:     '<div class="empty-state py-4"><i class="fa-regular fa-folder-open fa-2x text-muted mb-2"></i><p class="mb-0">Belum ada data.</p></div>',
            paginate: {
                first:    '<i class="fa-solid fa-angles-left"></i>',
                last:     '<i class="fa-solid fa-angles-right"></i>',
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next:     '<i class="fa-solid fa-chevron-right"></i>'
            }
        },
        pageLength:  10,
        lengthMenu:  [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        responsive:  true,
        autoWidth:   false,
        dom: "<'row align-items-center mb-2'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-2'<'col-sm-5 text-muted small'i><'col-sm-7'p>>"
    };

    /**
     * Initialize DataTable with merged defaults.
     * @param  {string|jQuery} selector  Table selector or jQuery object
     * @param  {object}        opts      Additional DataTables options
     * @returns {DataTables.Api}
     */
    App.initDT = function (selector, opts) {
        var $table = $(selector);
        if (!$table.length) return null;

        // Prevent double-initialization
        if ($.fn.DataTable.isDataTable($table)) {
            return $table.DataTable();
        }

        var mergedOpts = $.extend(true, {}, App.dtDefaults, opts || {});
        return $table.DataTable(mergedOpts);
    };

    /* ============================================================
       6. SELECT2 DEFAULT CONFIGURATION
       ============================================================ */
    App.s2Defaults = {
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: false,
        language: {
            noResults: function () { return 'Tidak ada pilihan.'; },
            searching: function () { return 'Mencari...'; },
            errorLoading: function () { return 'Gagal memuat data.'; },
            inputTooShort: function (args) {
                return 'Masukkan ' + (args.minimum - args.input.length) + ' karakter lagi.';
            },
            loadingMore: function () { return 'Memuat lebih banyak...'; }
        }
    };

    /**
     * Initialize Select2 with defaults.
     * @param  {string|jQuery} selector
     * @param  {object}        opts
     * @returns {jQuery}
     */
    App.initSelect2 = function (selector, opts) {
        var $el = $(selector);
        if (!$el.length) return $el;

        // Prevent double-initialization
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        var mergedOpts = $.extend({}, App.s2Defaults, opts || {});
        return $el.select2(mergedOpts);
    };

    /**
     * Initialize Select2 inside a modal (requires dropdownParent).
     */
    App.initSelect2InModal = function (selector, modalSelector, opts) {
        var $modal = $(modalSelector);
        var $el = App.initSelect2(selector, $.extend({ dropdownParent: $modal }, opts));
        $el.on('select2:open', function (e) {
            var evt = 'scroll.select2';
            $(e.target).parents().off(evt);
            $(window).off(evt);
        });
        return $el;
    };

    /* ============================================================
       7. FORM VALIDATION HELPER
       ============================================================ */
    /**
     * Show validation errors on form fields.
     * @param {jQuery} $form
     * @param {object} errors  { field_name: 'error message' }
     */
    App.showValidationErrors = function ($form, errors) {
        // Clear previous errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('').hide();

        $.each(errors, function (field, message) {
            var $field = $form.find('[name="' + field + '"]');
            if ($field.length) {
                $field.addClass('is-invalid');
                var $feedback = $field.siblings('.invalid-feedback');
                if (!$feedback.length) {
                    $feedback = $('<div class="invalid-feedback"></div>').insertAfter($field);
                }
                $feedback.text(message).show();
            }
        });
    };

    App.clearValidationErrors = function ($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('').hide();
    };

    /* ============================================================
       8. MODAL HELPER
       ============================================================ */
    App.openModal = function (selector) {
        var modal = bootstrap.Modal.getOrCreateInstance(document.querySelector(selector));
        modal.show();
    };

    App.closeModal = function (selector) {
        var el = document.querySelector(selector);
        if (!el) return;
        var modal = bootstrap.Modal.getInstance(el);
        if (modal) modal.hide();
    };

    /* ============================================================
       9. GLOBAL DOCUMENT READY
       ============================================================ */
    $(document).ready(function () {

        /* ─── Auto-initialize DataTables ──────────────────────────
           Any <table> with data-dt="true" gets auto-initialized.
           Example: <table class="table" data-dt="true" data-dt-order='[[0,"asc"]]'>
        */
        $('table[data-dt]').each(function () {
            var $table = $(this);
            var extraOpts = {};

            // Parse inline data attributes for common options
            if ($table.data('dt-order'))       extraOpts.order       = $table.data('dt-order');
            if ($table.data('dt-page-length')) extraOpts.pageLength  = $table.data('dt-page-length');
            if ($table.data('dt-searching') === false) extraOpts.searching = false;
            if ($table.data('dt-paging') === false)    extraOpts.paging    = false;
            if ($table.data('dt-info') === false)      extraOpts.info      = false;

            App.initDT($table, extraOpts);
        });

        /* ─── Auto-initialize Select2 ─────────────────────────────
           Any <select> with class select2 or data-select2 gets auto-initialized.
        */
        $('select.select2, select[data-select2]').each(function () {
            var $el = $(this);
            var placeholder = $el.data('placeholder') || $el.attr('placeholder') || 'Pilih...';
            App.initSelect2($el, {
                placeholder:  placeholder,
                allowClear:   $el.data('allow-clear') === true
            });
        });

        /* ─── SweetAlert2 for flash alerts ───────────────────────
           Any element with data-swal="success|error|warning|info" fires a toast on load.
           Usage in PHP view: <div data-swal="success" data-swal-message="Data berhasil disimpan" hidden></div>
        */
        $('[data-swal]').each(function () {
            var type    = $(this).data('swal');
            var message = $(this).data('swal-message') || '';
            var title   = $(this).data('swal-title') || '';
            if (!message && !title) return;
            switch (type) {
                case 'success': App.toastSuccess(message, title || 'Berhasil!'); break;
                case 'error':   App.toastError(message, title || 'Gagal!'); break;
                case 'warning': App.toastWarning(message, title); break;
                default:        App.toastInfo(message, title); break;
            }
        });

        /* ─── Confirm for data-confirm buttons ───────────────────
           Any button/link with data-confirm triggers SweetAlert confirm before proceeding.
           Usage: <button data-confirm="Yakin ingin menghapus?" data-confirm-type="delete">Hapus</button>
                  <a href="/delete/1" data-confirm="Hapus?">Hapus</a>
        */
        $(document).on('click', '[data-confirm]', function (e) {
            e.preventDefault();
            var $this   = $(this);
            var message = $this.data('confirm') || 'Apakah Anda yakin?';
            var type    = $this.data('confirm-type') || 'default';
            var confirmFn;

            switch (type) {
                case 'delete':     confirmFn = App.confirmDelete;     break;
                case 'approve':    confirmFn = App.confirmApprove;    break;
                case 'reject':     confirmFn = App.confirmReject;     break;
                case 'return':     confirmFn = App.confirmReturn;     break;
                case 'deactivate': confirmFn = App.confirmDeactivate; break;
                default:           confirmFn = App.confirm;           break;
            }

            confirmFn({ text: message }).then(function (result) {
                if (!result.isConfirmed) return;

                // If it's a link, follow it
                if ($this.is('a') && $this.attr('href')) {
                    window.location.href = $this.attr('href');
                    return;
                }

                // If it's a button inside a form, submit the form
                var $form = $this.closest('form');
                if ($form.length) {
                    $form.submit();
                    return;
                }

                // Trigger click event again without confirmation
                $this.removeAttr('data-confirm').trigger('click');
            });
        });

        /* ─── Auto-submit for select with data-autosubmit ────────
           Replaces inline onchange="this.form.submit()"
        */
        $(document).on('change', 'select[data-autosubmit]', function () {
            $(this).closest('form').submit();
        });

        /* ─── Global Table Dropdown Fixer (Popper Strategy Fixed) ────
           Memastikan semua dropdown di dalam .table-responsive / tabel
           menggunakan Popper strategy 'fixed' sehingga melayang keluar
           dari card dan tidak terpotong oleh overflow-x / overflow: hidden.
        */
        $(document).on('show.bs.dropdown', function (e) {
            var $trigger = $(e.target);
            if ($trigger.closest('.table-responsive, .table, .table-card, .card-body').length) {
                var dropdownInstance = bootstrap.Dropdown.getInstance(e.target);
                if (dropdownInstance) {
                    dropdownInstance._config = dropdownInstance._config || {};
                    var existingPopperConfig = dropdownInstance._config.popperConfig;
                    dropdownInstance._config.popperConfig = function (defaultConfig) {
                        var base = typeof existingPopperConfig === 'function' 
                            ? existingPopperConfig(defaultConfig) 
                            : (typeof existingPopperConfig === 'object' ? existingPopperConfig : defaultConfig);
                        return $.extend(true, {}, base, {
                            strategy: 'fixed'
                        });
                    };
                }
            }
        });

        /* ─── Universal Click-to-Copy Handler (.btn-copy-inline) ────
           Menyalin teks yang ada di atribut data-clipboard ke clipboard
           dengan feedback visual icon checkmark dan toast notification.
        */
        $(document).on('click', '.btn-copy-inline', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var text = $btn.attr('data-clipboard');
            if (!text) return;

            var originalHtml = $btn.html();

            function showCopySuccess() {
                $btn.addClass('copied').html('<i class="fa-solid fa-check"></i>');
                if (window.App && typeof App.toastSuccess === 'function') {
                    App.toastSuccess('Berhasil disalin: ' + text);
                }
                setTimeout(function () {
                    $btn.removeClass('copied').html(originalHtml);
                }, 1500);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(showCopySuccess).catch(function () {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }

            function fallbackCopy(val) {
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(val).select();
                try {
                    document.execCommand('copy');
                } catch (err) {}
                $temp.remove();
                showCopySuccess();
            }
        });

        /* ============================================================
           Ensure all Bootstrap modals are appended directly to body
           so any parent container styling/stacking context never traps
           the modal behind the modal backdrop.
           ============================================================ */
        $(document).on('show.bs.modal', '.modal', function () {
            if (!$(this).parent().is('body')) {
                $(this).appendTo('body');
            }
        });

    }); // end document.ready

}(jQuery));
