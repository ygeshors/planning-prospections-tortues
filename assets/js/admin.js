/**
 * Planning Prospections Tortues Marines – JS Admin
 */
(function ($) {
    'use strict';

    /* ── Changement de statut d'une inscription (select inline) ── */
    $(document).on('change', '.shl-status-select', function () {
        var $sel   = $(this);
        var id     = $sel.data('id');
        var status = $sel.val();

        $sel.prop('disabled', true);

        $.post(shlAdmin.ajax, {
            action:  'shl_admin_registration_status',
            nonce:   shlAdmin.nonce,
            id:      id,
            status:  status
        }, function (res) {
            $sel.prop('disabled', false);
            $sel.removeClass('shl-status-pending shl-status-validated shl-status-refused')
                .addClass('shl-status-' + status);

            if (!res.success) {
                alert('Erreur : ' + (res.data || 'impossible de mettre à jour le statut.'));
            }
        }).fail(function () {
            $sel.prop('disabled', false);
            alert('Erreur réseau.');
        });
    });

    /* ── Copier la liste des inscrits ── */
    $(document).on('click', '.shl-copy-btn', function () {
        var $btn   = $(this);
        var slotId = $btn.data('slot');

        $.post(shlAdmin.ajax, {
            action:   'shl_admin_copy_registrants',
            nonce:    shlAdmin.nonce,
            slot_id:  slotId
        }, function (res) {
            if (res.success && res.data.text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(res.data.text).then(function () {
                        shlFlash($btn, '✅ Copié !');
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = res.data.text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    shlFlash($btn, '✅ Copié !');
                }
            } else {
                shlFlash($btn, 'Aucun inscrit');
            }
        });
    });

    function shlFlash($el, text) {
        var orig = $el.text();
        $el.text(text);
        setTimeout(function () { $el.text(orig); }, 2000);
    }

})(jQuery);
