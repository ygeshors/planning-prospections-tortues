/**
 * Planning Prospections Tortues Marines – JS Public v2.1
 *
 * Vues : mois / semaine / liste, navigation, modal, inscription, carte zones.
 * Nouveautés v2 : fill-rate colors, "dernières places" badge, swipe mobile, skeleton loading.
 */
(function ($) {
    'use strict';

    /* ══════════════════════════════════════════════════════
       ÉTAT DE L'APPLICATION
    ══════════════════════════════════════════════════════ */
    var state = {
        view:       'month',
        year:       new Date().getFullYear(),
        month:      new Date().getMonth() + 1,
        weekStart:  _getMondayOf(new Date()),
        slotsCache: {}
    };

    // Zone map dans la modal
    var modalZoneMap     = null;
    var modalZoneGeoJSON = null;
    var modalZoneMapInit = false;

    // Touch swipe
    var touchStartX = 0;
    var touchStartY = 0;

    var TYPE_ICON  = { foot: '🚶', drone: '🚁', mixed: '🔀' };
    var TYPE_LABEL = { foot: 'À pied', drone: 'Drone', mixed: 'Mixte' };
    var MONTH_FR   = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    var DAY_FR     = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
    var DAY_FULL   = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];

    /* ══════════════════════════════════════════════════════
       INIT
    ══════════════════════════════════════════════════════ */
    $(function () {
        if (!$('#shl-planning').length) { return; }

        // Couleur principale
        if (shlTortues.color) {
            document.querySelector('.shl-planning').style.setProperty('--shl-sea', shlTortues.color);
        }

        // Onglets de vue
        $(document).on('click', '.shl-tab', function () {
            var view = $(this).data('view');
            $('.shl-tab').removeClass('shl-tab-active');
            $(this).addClass('shl-tab-active');
            state.view = view;
            render();
        });

        // Navigation
        $('#shl-prev').on('click', navigatePrev);
        $('#shl-next').on('click', navigateNext);
        $('#shl-today').on('click', navigateToday);

        // Fermer la modal
        $(document).on('click', '#shl-modal-close, #shl-modal-overlay', function (e) {
            if (e.target === this) { closeModal(); }
        });
        $(document).on('keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });

        // Formulaire d'inscription
        $(document).on('submit', '#shl-reg-form', handleRegistration);

        // Zone map dans la modal
        $(document).on('click', '#shl-modal-zone-toggle', function () {
            var $map = $('#shl-modal-zone-map');
            var open = $map.is(':visible');
            $map.slideToggle(200);
            $(this).find('.shl-zone-arrow').text(open ? '▼' : '▲');
            if (!open && !modalZoneMapInit && modalZoneGeoJSON && window.L) {
                modalZoneMapInit = true;
                setTimeout(function () {
                    modalZoneMap = L.map('shl-modal-zone-map', { zoomControl: true, attributionControl: false });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(modalZoneMap);
                    var zlayer = L.geoJSON(JSON.parse(modalZoneGeoJSON), {
                        style: function (f) {
                            var t = f.geometry ? f.geometry.type : '';
                            return (t === 'Polygon' || t === 'MultiPolygon')
                                ? { color: shlTortues.color || '#2E86AB', fillOpacity: 0.2, weight: 3 }
                                : { color: '#e8a23a', weight: 4 };
                        }
                    }).addTo(modalZoneMap);
                    if (zlayer.getLayers().length > 0) {
                        modalZoneMap.fitBounds(zlayer.getBounds(), { padding: [15, 15] });
                    }
                    modalZoneMap.invalidateSize();
                }, 250);
            }
        });

        // ── Dark mode ──────────────────────────────────────────────────────────
        var $planning = $('#shl-planning');

        if (localStorage.getItem('shl-dark-mode') === '1') {
            $planning.addClass('shl-dark');
            $('#shl-dark-toggle').text('☀️');
        }

        $('#shl-dark-toggle').on('click', function () {
            var dark = $planning.hasClass('shl-dark');
            $planning.toggleClass('shl-dark', !dark);
            $(this).text(dark ? '🌙' : '☀️');
            localStorage.setItem('shl-dark-mode', dark ? '0' : '1');
        });

        // ── Navigation mobile : scroll vers le calendrier ──────────────────────
        $('#shl-mobile-cal').on('click', function () {
            var top = ($('#shl-planning').offset() || { top: 0 }).top - 20;
            $('html, body').animate({ scrollTop: top }, 280);
        });

        // Swipe mobile pour naviguer entre mois/semaines
        var $body = $('#shl-planning');
        $body[0].addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        $body[0].addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].screenX - touchStartX;
            var dy = e.changedTouches[0].screenY - touchStartY;
            // Swipe horizontal significatif (>50px) et plus horizontal que vertical
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                if (dx < 0) { navigateNext(); }
                else        { navigatePrev(); }
            }
        }, { passive: true });

        render();
    });

    /* ══════════════════════════════════════════════════════
       NAVIGATION
    ══════════════════════════════════════════════════════ */
    function navigatePrev() {
        if (state.view === 'week') {
            state.weekStart = _addDays(state.weekStart, -7);
            _syncMonthFromWeek();
        } else {
            state.month--;
            if (state.month < 1) { state.month = 12; state.year--; }
        }
        render();
    }

    function navigateNext() {
        if (state.view === 'week') {
            state.weekStart = _addDays(state.weekStart, 7);
            _syncMonthFromWeek();
        } else {
            state.month++;
            if (state.month > 12) { state.month = 1; state.year++; }
        }
        render();
    }

    function navigateToday() {
        var now = new Date();
        state.year  = now.getFullYear();
        state.month = now.getMonth() + 1;
        state.weekStart = _getMondayOf(now);
        render();
    }

    function _syncMonthFromWeek() {
        var thu = _addDays(state.weekStart, 3);
        state.year  = thu.getFullYear();
        state.month = thu.getMonth() + 1;
    }

    /* ══════════════════════════════════════════════════════
       RENDU PRINCIPAL
    ══════════════════════════════════════════════════════ */
    function render() {
        updatePeriodLabel();
        showSkeleton();

        if (state.view === 'month') {
            loadSlots(state.year, state.month, renderMonth);
        } else if (state.view === 'week') {
            var sunday = _addDays(state.weekStart, 6);
            var m1 = state.weekStart.getMonth() + 1, y1 = state.weekStart.getFullYear();
            var m2 = sunday.getMonth() + 1,           y2 = sunday.getFullYear();
            if (m1 === m2 && y1 === y2) {
                loadSlots(y1, m1, renderWeek);
            } else {
                loadSlots(y1, m1, function () { loadSlots(y2, m2, renderWeek); });
            }
        } else {
            loadSlots(state.year, state.month, renderList);
        }
    }

    function updatePeriodLabel() {
        var label = '';
        if (state.view === 'week') {
            var sun = _addDays(state.weekStart, 6);
            label = 'Sem. du ' + _fmt(state.weekStart) + ' au ' + _fmt(sun);
        } else {
            label = MONTH_FR[state.month - 1] + ' ' + state.year;
        }
        $('#shl-period-label').text(label);
    }

    /* ── Skeleton loading ── */
    function showSkeleton() {
        if (state.view !== 'month') {
            $('#shl-cal-body').html('<div class="shl-loading"><span class="shl-spinner"></span> Chargement…</div>');
            return;
        }
        var html = '<div class="shl-skeleton-grid">';
        for (var i = 0; i < 35; i++) {
            html += '<div class="shl-skeleton-cell">';
            html += '<div class="shl-skeleton shl-skeleton-num"></div>';
            if (i % 5 === 0) { html += '<div class="shl-skeleton shl-skeleton-bar"></div>'; }
            if (i % 7 === 1) { html += '<div class="shl-skeleton shl-skeleton-bar-2"></div>'; }
            html += '</div>';
        }
        html += '</div>';
        $('#shl-cal-body').html(html);
    }

    /* ══════════════════════════════════════════════════════
       CHARGEMENT AJAX + CACHE
    ══════════════════════════════════════════════════════ */
    function loadSlots(year, month, callback) {
        var key = year + '-' + month;
        if (state.slotsCache[key]) { callback(state.slotsCache[key]); return; }

        $.post(shlTortues.ajax, {
            action: 'shl_get_calendar_data',
            nonce:  shlTortues.nonce,
            year:   year,
            month:  month
        }, function (res) {
            if (res.success) {
                state.slotsCache[key] = res.data;
                callback(res.data);
            } else {
                showError('Impossible de charger le planning.');
            }
        }).fail(function () { showError('Erreur réseau, veuillez réessayer.'); });
    }

    function showError(msg) {
        $('#shl-cal-body').html('<div class="shl-no-slots"><div class="shl-no-slots-icon">⚠️</div><p>' + _esc(msg) + '</p></div>');
    }

    /* ══════════════════════════════════════════════════════
       UTILITAIRE FILL-RATE
    ══════════════════════════════════════════════════════ */
    function _fillCls(left, total) {
        if (!total || left === 0) { return 'full'; }
        var pct = left / total;
        if (pct > 0.5) { return 'ok'; }
        if (pct > 0.2) { return 'warn'; }
        return 'low';
    }

    function _fillDot(left, total) {
        var cls = _fillCls(left, total);
        if (cls === 'full') { return ''; }
        return '<span class="shl-fill-dot"></span>';
    }

    function _lastPlacesBadge(left, total) {
        if (left > 0 && left <= 2 && left < total) {
            return ' <span class="shl-last-places-badge">Dernières places</span>';
        }
        return '';
    }

    /* ══════════════════════════════════════════════════════
       VUE MOIS
    ══════════════════════════════════════════════════════ */
    function renderMonth(slots) {
        var byDate = {};
        slots.forEach(function (s) {
            if (!byDate[s.date]) { byDate[s.date] = []; }
            byDate[s.date].push(s);
        });

        var today  = _dateStr(new Date());
        var first  = new Date(state.year, state.month - 1, 1);
        var last   = new Date(state.year, state.month, 0);
        var cursor = _getMondayOf(first);
        var endDate= _getSundayOf(last);

        var html = '<div class="shl-month-grid">';
        html += '<div class="shl-month-weekdays">';
        DAY_FR.forEach(function (d) { html += '<div class="shl-weekday">' + _esc(d) + '</div>'; });
        html += '</div><div class="shl-month-days">';

        while (cursor <= endDate) {
            var ds       = _dateStr(cursor);
            var inMonth  = cursor.getMonth() + 1 === state.month;
            var isToday  = ds === today;
            var daySlots = byDate[ds] || [];

            var cls = 'shl-day';
            if (!inMonth)        { cls += ' shl-day-other-month'; }
            if (isToday)         { cls += ' shl-day-today'; }
            if (daySlots.length) { cls += ' shl-day-has-slots'; }

            html += '<div class="' + cls + '">';
            html += '<div class="shl-day-num">' + cursor.getDate() + '</div>';

            if (daySlots.length) {
                html += '<div class="shl-day-slots">';
                daySlots.forEach(function (s) {
                    var fillCls  = s.status === 'full' ? 'full' : _fillCls(s.places_left, s.places_total);
                    var chipType = s.status === 'full' ? 'full' : s.type;
                    var chipCls  = 'shl-slot-chip shl-chip-' + chipType + ' shl-fill-' + fillCls;
                    html += '<div class="' + chipCls + '" data-slot-id="' + s.id + '" title="' + _esc(s.zone_name) + ' ' + s.time_start + '">';
                    if (fillCls !== 'full') { html += '<span class="shl-fill-dot"></span>'; }
                    html += _esc(s.time_start) + ' ' + TYPE_ICON[s.type] + ' ' + _esc(s.zone_name);
                    html += '</div>';
                });
                html += '</div>';
            }
            html += '</div>';
            cursor = _addDays(cursor, 1);
        }

        html += '</div></div>';
        _setCalBody(html);

        $('#shl-cal-body').on('click', '.shl-slot-chip', function (e) {
            e.stopPropagation();
            openSlotModal($(this).data('slot-id'));
        });
    }

    /* ══════════════════════════════════════════════════════
       VUE SEMAINE
    ══════════════════════════════════════════════════════ */
    function renderWeek(slots) {
        var days = [];
        for (var i = 0; i < 7; i++) { days.push(_addDays(state.weekStart, i)); }

        var today  = _dateStr(new Date());
        var byDate = {};
        slots.forEach(function (s) {
            if (!byDate[s.date]) { byDate[s.date] = []; }
            byDate[s.date].push(s);
        });

        var html = '<div class="shl-week-grid">';
        days.forEach(function (d, i) {
            var ds       = _dateStr(d);
            var isToday  = ds === today;
            var daySlots = byDate[ds] || [];

            html += '<div class="shl-week-day">';
            html += '<div class="shl-week-day-header' + (isToday ? ' shl-today-header' : '') + '">';
            html += '<span class="shl-week-day-num' + (isToday ? ' shl-today-num' : '') + '">' + d.getDate() + '</span>';
            html += '<br><span style="font-size:9px;text-transform:uppercase;letter-spacing:.5px">' + DAY_FR[i] + '</span>';
            html += '</div><div class="shl-week-slots">';

            if (daySlots.length) {
                daySlots.forEach(function (s) {
                    var fillCls  = _fillCls(s.places_left, s.places_total);
                    var fillPct  = s.places_total > 0 ? Math.round(s.places_left / s.places_total * 100) : 0;
                    var barColor = fillCls === 'ok' ? '#27ae60' : (fillCls === 'warn' ? '#e8a23a' : '#e05252');
                    html += '<div class="shl-week-slot-card shl-week-slot-' + s.type + '" data-slot-id="' + s.id + '">';
                    html += '<div class="shl-week-slot-time">' + _esc(s.time_start) + '</div>';
                    html += '<div class="shl-week-slot-name">' + TYPE_ICON[s.type] + ' ' + _esc(s.zone_name) + '</div>';
                    html += '<div class="shl-week-slot-places shl-places-' + fillCls + '">' + s.places_left + '/' + s.places_total + '</div>';
                    html += '<div class="shl-week-fill-bar"><div class="shl-week-fill-bar-inner" style="width:' + fillPct + '%;background:' + barColor + '"></div></div>';
                    html += '</div>';
                });
            } else {
                html += '<div class="shl-week-empty">—</div>';
            }

            html += '</div></div>';
        });

        html += '</div>';
        _setCalBody(html);

        $('#shl-cal-body').on('click', '.shl-week-slot-card', function () {
            openSlotModal($(this).data('slot-id'));
        });
    }

    /* ══════════════════════════════════════════════════════
       VUE LISTE / AGENDA
    ══════════════════════════════════════════════════════ */
    function renderList(slots) {
        if (!slots.length) {
            $('#shl-cal-body').html('<div class="shl-no-slots"><div class="shl-no-slots-icon">🌊</div><p>Aucune prospection prévue pour ce mois.</p></div>');
            return;
        }

        var groups = {}, order = [];
        slots.forEach(function (s) {
            if (!groups[s.date]) { groups[s.date] = []; order.push(s.date); }
            groups[s.date].push(s);
        });

        var html = '<div class="shl-list-view">';
        order.forEach(function (date) {
            var d = new Date(date + 'T00:00:00');
            var dayLabel = DAY_FULL[(d.getDay() + 6) % 7] + ' ' + d.getDate() + ' ' + MONTH_FR[d.getMonth()] + ' ' + d.getFullYear();

            html += '<div class="shl-list-date-group">';
            html += '<div class="shl-list-date-header">' + _esc(dayLabel) + '</div>';

            groups[date].forEach(function (s) {
                var left     = s.places_left;
                var total    = s.places_total;
                var fillCls  = _fillCls(left, total);
                var fillPct  = total > 0 ? Math.round((total - left) / total * 100) : 100;
                var barClass = 'shl-fill-bar-' + fillCls;
                var countCls = fillCls === 'ok' ? 'shl-places-ok' : (fillCls === 'warn' ? 'shl-places-warn' : 'shl-places-full');

                var statusBadge = '';
                if (s.status === 'full')      { statusBadge = '<span class="shl-list-status-badge shl-status-full">Complet</span>'; }
                else if (s.status === 'done') { statusBadge = '<span class="shl-list-status-badge shl-status-done">Réalisé</span>'; }

                html += '<div class="shl-list-card" data-slot-id="' + s.id + '">';
                html += '<div class="shl-list-type-badge shl-badge-' + s.type + '">' + TYPE_ICON[s.type] + '</div>';
                html += '<div class="shl-list-info">';
                html += '<p class="shl-list-name">' + _esc(s.zone_name) + _lastPlacesBadge(left, total) + '</p>';
                html += '<div class="shl-list-meta">';
                html += '<span>⏰ ' + _esc(s.time_start) + '</span>';
                html += '<span>📍 ' + _esc(s.commune) + '</span>';
                html += '<span>' + _esc(TYPE_LABEL[s.type] || s.type) + '</span>';
                if (statusBadge) { html += statusBadge; }
                html += '</div></div>';

                html += '<div class="shl-list-fill-wrap">';
                html += '<span class="shl-list-fill-count ' + countCls + '">' + left + '/' + total + '</span>';
                html += '<div class="shl-list-fill-bar-wrap"><div class="shl-list-fill-bar ' + barClass + '" style="width:' + (100 - fillPct) + '%"></div></div>';
                html += '<span class="shl-places-label">places</span>';
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
        });

        html += '</div>';
        _setCalBody(html);

        $('#shl-cal-body').on('click', '.shl-list-card', function () {
            openSlotModal($(this).data('slot-id'));
        });
    }

    /* ══════════════════════════════════════════════════════
       MODAL DÉTAIL DU CRÉNEAU
    ══════════════════════════════════════════════════════ */
    function openSlotModal(slotId) {
        var $overlay = $('#shl-modal-overlay');
        var $content = $('#shl-modal-content');

        $content.html('<div class="shl-loading" style="padding:40px"><span class="shl-spinner"></span></div>');
        $overlay.addClass('shl-is-open').removeAttr('hidden');
        $('body').addClass('shl-modal-open');

        $.post(shlTortues.ajax, {
            action:  'shl_get_slot_details',
            nonce:   shlTortues.nonce,
            slot_id: slotId
        }, function (res) {
            if (res.success) {
                modalZoneMap     = null;
                modalZoneGeoJSON = res.data.zone_geojson || null;
                modalZoneMapInit = false;
                $content.html(buildModalHTML(res.data));
            } else {
                $content.html('<div class="shl-modal-body"><p>⚠ ' + _esc(res.data || 'Erreur') + '</p></div>');
            }
        }).fail(function () {
            $content.html('<div class="shl-modal-body"><p>⚠ Erreur réseau.</p></div>');
        });
    }

    function buildModalHTML(s) {
        var typeCls   = 'shl-type-' + s.type;
        var typeLabel = TYPE_ICON[s.type] + ' ' + (TYPE_LABEL[s.type] || s.type);
        var timeHtml  = s.time_start + (s.time_end ? ' → ' + s.time_end : '');
        var placesLeft= s.places_left;
        var fillCls   = _fillCls(placesLeft, s.places_total);
        var placeCls  = fillCls === 'ok' ? 'places-ok' : (fillCls === 'warn' ? 'places-warn' : 'places-full');

        var html = '';

        // En-tête coloré
        html += '<div class="shl-modal-header ' + typeCls + '">';
        html += '<div class="shl-modal-type-badge">' + typeLabel + '</div>';
        html += '<h2 class="shl-modal-title" id="shl-modal-title">' + _esc(s.zone_name) + '</h2>';
        html += '<p class="shl-modal-commune">📍 ' + _esc(s.commune) + '</p>';
        html += '</div>';

        html += '<div class="shl-modal-body">';

        // Infos
        html += '<div class="shl-slot-infos">';
        html += '<div class="shl-info-row"><span class="shl-info-icon">📅</span><span>' + _esc(s.date_formatted) + '</span></div>';
        html += '<div class="shl-info-row"><span class="shl-info-icon">⏰</span><span>' + _esc(timeHtml) + '</span></div>';
        if (s.meeting_point) {
            html += '<div class="shl-info-row"><span class="shl-info-icon">📌</span><span>' + _esc(s.meeting_point) + '</span></div>';
        }
        html += '<div class="shl-info-row"><span class="shl-info-icon">👥</span>';
        html += '<span class="shl-places-display ' + placeCls + '">';
        html += placesLeft + ' place' + (placesLeft > 1 ? 's' : '') + ' disponible' + (placesLeft > 1 ? 's' : '') + ' sur ' + s.places_total;
        if (placesLeft > 0 && placesLeft <= 2) { html += ' <span class="shl-last-places-badge">Dernières places</span>'; }
        html += '</span></div>';
        if (s.referent) {
            html += '<div class="shl-info-row"><span class="shl-info-icon">🧭</span><span>Référent : ' + _esc(s.referent) + '</span></div>';
        }
        html += '</div>';

        // Zone ou GPS
        if (s.zone_geojson) {
            html += '<button type="button" id="shl-modal-zone-toggle" style="display:flex;align-items:center;justify-content:space-between;width:100%;background:#e8f4ff;border:1.5px solid #c0d8ec;border-radius:10px;padding:11px 14px;font-size:13px;font-weight:700;color:#1a5f7a;cursor:pointer;margin:10px 0;text-align:left;font-family:inherit">';
            html += '<span>🏖️ Voir la zone de prospection</span><span class="shl-zone-arrow">▼</span>';
            html += '</button>';
            html += '<div id="shl-modal-zone-map" style="display:none;height:220px;border-radius:10px;overflow:hidden;margin-bottom:10px"></div>';
        } else if (s.latitude && s.longitude) {
            var mapsUrl = 'https://www.openstreetmap.org/?mlat=' + s.latitude + '&mlon=' + s.longitude + '&zoom=16';
            html += '<a href="' + mapsUrl + '" target="_blank" rel="noopener" class="shl-map-link">🗺️ Voir le point de départ</a>';
        }

        // Consignes
        var consignes = s.instructions || s.general_instructions;
        if (consignes) {
            html += '<div class="shl-modal-instructions"><h4>📋 Consignes</h4>';
            html += '<p>' + _esc(consignes).replace(/\n/g, '<br>') + '</p></div>';
        }

        // Inscrits
        if (s.names && s.names.length) {
            html += '<div class="shl-modal-names"><h4>👤 Déjà inscrits</h4><ul>';
            s.names.forEach(function (n) { html += '<li>' + _esc(n) + '</li>'; });
            html += '</ul></div>';
        }

        // Statut
        var statusLabels = { open: '✅ Ouvert aux inscriptions', full: '🚫 Complet', cancelled: '❌ Annulé', done: '🏁 Prospection réalisée' };
        html += '<div class="shl-status-block shl-status-' + s.status + '">' + (statusLabels[s.status] || s.status) + '</div>';

        // Formulaire
        if (s.status === 'open' && s.places_left > 0) {
            html += buildRegisterForm(s.id);
        } else if (s.status === 'full') {
            html += '<div class="shl-form-notice shl-notice-error" style="display:block">Ce créneau est complet. Consultez les autres dates disponibles.</div>';
        }

        html += '</div>';
        return html;
    }

    function buildRegisterForm(slotId) {
        var html = '<div class="shl-register-section">';
        html += '<h3>✍️ S\'inscrire à ce créneau</h3>';
        html += '<form id="shl-reg-form" data-slot-id="' + slotId + '">';

        html += '<div class="shl-form-row">';
        html += '<div class="shl-form-field"><label>Prénom <span class="shl-required">*</span></label><input type="text" name="firstname" required placeholder="Jean"></div>';
        html += '<div class="shl-form-field"><label>Nom <span class="shl-required">*</span></label><input type="text" name="lastname" required placeholder="Dupont"></div>';
        html += '</div>';

        html += '<div class="shl-form-row">';
        html += '<div class="shl-form-field"><label>Email <span class="shl-required">*</span></label><input type="email" name="email" required placeholder="jean@exemple.fr"></div>';
        html += '<div class="shl-form-field"><label>Téléphone</label><input type="tel" name="phone" placeholder="06 00 00 00 00"></div>';
        html += '</div>';

        html += '<div class="shl-form-row">';
        html += '<div class="shl-form-field"><label>Adhérent·e SHL ?</label><select name="is_member"><option value="0">Non</option><option value="1">Oui</option></select></div>';
        html += '</div>';

        html += '<div class="shl-form-row">';
        html += '<div class="shl-form-field shl-form-field-full"><label>Commentaire (facultatif)</label><textarea name="comment" rows="3" placeholder="Informations utiles pour l\'organisation…"></textarea></div>';
        html += '</div>';

        html += '<div class="shl-form-row">';
        html += '<div class="shl-form-field shl-form-field-full">';
        html += '<label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;line-height:1.5;width:100%;box-sizing:border-box;font-weight:600">';
        html += '<input type="checkbox" name="accepted_rules" value="1" required style="flex-shrink:0;margin-top:3px;width:16px;height:16px;accent-color:var(--shl-sea)">';
        html += '<span style="flex:1;min-width:0;word-wrap:break-word">J\'ai pris connaissance des consignes de prospection et je m\'engage à les respecter.&nbsp;<span style="color:#e05555">*</span></span>';
        html += '</label>';
        html += '</div></div>';

        html += '<button type="submit" class="shl-submit-btn">🐢 S\'inscrire à la prospection</button>';
        html += '<div class="shl-form-notice" id="shl-reg-notice"></div>';
        html += '</form></div>';
        return html;
    }

    function closeModal() {
        $('#shl-modal-overlay').removeClass('shl-is-open').attr('hidden', '');
        $('body').removeClass('shl-modal-open');
        $('#shl-modal-content').html('');
        if (modalZoneMap) { modalZoneMap.remove(); modalZoneMap = null; }
        modalZoneGeoJSON = null;
        modalZoneMapInit = false;
        state.slotsCache = {};
    }

    /* ══════════════════════════════════════════════════════
       INSCRIPTION
    ══════════════════════════════════════════════════════ */
    function handleRegistration(e) {
        e.preventDefault();
        var $form   = $(this);
        var $btn    = $form.find('.shl-submit-btn');
        var $notice = $('#shl-reg-notice');
        var slotId  = $form.data('slot-id');

        $btn.prop('disabled', true).html('⏳ Envoi en cours…');
        $notice.removeClass('shl-notice-success shl-notice-error').hide();

        var data = $form.serializeArray();
        data.push({ name: 'action',  value: 'shl_register_slot' });
        data.push({ name: 'nonce',   value: shlTortues.nonce });
        data.push({ name: 'slot_id', value: slotId });

        $.post(shlTortues.ajax, data, function (res) {
            if (res.success) {
                $form.slideUp(200);
                $notice.addClass('shl-notice-success').text(res.data.message).show();
            } else {
                $notice.addClass('shl-notice-error').text(res.data.message || 'Une erreur est survenue.').show();
                $btn.prop('disabled', false).html('🐢 S\'inscrire à la prospection');
            }
        }).fail(function () {
            $notice.addClass('shl-notice-error').text('Erreur réseau, veuillez réessayer.').show();
            $btn.prop('disabled', false).html('🐢 S\'inscrire à la prospection');
        });
    }

    /* ══════════════════════════════════════════════════════
       UTILITAIRES
    ══════════════════════════════════════════════════════ */

    /* Injecte du HTML dans le corps du calendrier avec un fondu */
    function _setCalBody(html) {
        var el = document.getElementById('shl-cal-body');
        el.innerHTML = html;
        el.style.opacity = '0';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                el.style.opacity = '';
            });
        });
    }

    function _getMondayOf(date) {
        var d = new Date(date); var day = d.getDay();
        var diff = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diff); d.setHours(0, 0, 0, 0); return d;
    }

    function _getSundayOf(date) { return _addDays(_getMondayOf(date), 6); }

    function _addDays(date, n) { var d = new Date(date); d.setDate(d.getDate() + n); return d; }

    function _dateStr(date) {
        return date.getFullYear() + '-' +
               String(date.getMonth() + 1).padStart(2, '0') + '-' +
               String(date.getDate()).padStart(2, '0');
    }

    function _fmt(date) { return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear(); }

    function _esc(str) {
        if (!str) { return ''; }
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})(jQuery);
