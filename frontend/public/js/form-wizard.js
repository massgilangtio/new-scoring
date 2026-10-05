/**
 * Color Admin form_wizards — lightweight multi-pane step controller (UX only).
 * Markup: [data-form-wizard] > [data-wizard-pane="1|2|..."] + [data-wizard-prev|next|finish]
 */
(function (window, $) {
    'use strict';

    function initWizard(root) {
        var $root = $(root);
        if ($root.data('wizardReady')) {
            return;
        }
        $root.data('wizardReady', true);

        var $panes = $root.find('[data-wizard-pane]');
        var total = $panes.length;
        if (total < 1) {
            return;
        }

        var step = Math.max(1, parseInt($root.attr('data-wizard-start') || '1', 10) || 1);

        function show(n) {
            step = Math.max(1, Math.min(total, n));
            $panes.addClass('d-none').filter('[data-wizard-pane="' + step + '"]').removeClass('d-none');
            $root.find('[data-wizard-prev]').toggleClass('invisible', step === 1).prop('disabled', step === 1);
            $root.find('[data-wizard-next]').toggleClass('d-none', step === total);
            $root.find('[data-wizard-finish]').toggleClass('d-none', step !== total);

            $root.find('.nav-wizards-container .nav-item').each(function (idx) {
                var $link = $(this).find('.nav-link');
                $link.removeClass('active completed disabled');
                if (idx + 1 < step) {
                    $link.addClass('completed');
                } else if (idx + 1 === step) {
                    $link.addClass('active');
                } else {
                    $link.addClass('disabled');
                }
            });

            $root.trigger('wizard:step', [step, total]);
        }

        function validateCurrent() {
            var $pane = $panes.filter('[data-wizard-pane="' + step + '"]');
            var valid = true;
            $pane.find('input, select, textarea').each(function () {
                if (this.disabled || this.readOnly) {
                    return;
                }
                var $el = $(this);
                if ($el.is('select') && $el.hasClass('select2-hidden-accessible')) {
                    if (this.required && !$el.val()) {
                        valid = false;
                        try { $el.select2('open'); } catch (err) { /* ignore */ }
                        return false;
                    }
                    return;
                }
                if (typeof this.checkValidity === 'function' && !this.checkValidity()) {
                    this.reportValidity();
                    valid = false;
                    return false;
                }
            });
            return valid;
        }

        $root.on('click', '[data-wizard-next]', function (e) {
            e.preventDefault();
            if (!validateCurrent()) {
                return;
            }
            var before = $.Event('wizard:beforeNext');
            $root.trigger(before, [step, total]);
            if (before.isDefaultPrevented()) {
                return;
            }
            show(step + 1);
        });

        $root.on('click', '[data-wizard-prev]', function (e) {
            e.preventDefault();
            show(step - 1);
        });

        $root.on('click', '.nav-wizards-container .nav-link.completed', function (e) {
            e.preventDefault();
            var idx = $(this).closest('.nav-item').index() + 1;
            show(idx);
        });

        $root.data('wizardGo', show);
        show(step);
    }

    window.App = window.App || {};
    App.initFormWizard = function (selector) {
        $(selector).each(function () {
            initWizard(this);
        });
    };

    $(function () {
        $('[data-form-wizard]').each(function () {
            initWizard(this);
        });
    });
})(window, jQuery);
