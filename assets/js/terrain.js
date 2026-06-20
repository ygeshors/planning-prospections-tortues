/**
 * Planning Prospections Tortues Marines – JS Terrain (mobile)
 *
 * Fonctions : GPS, compression photo, upload AJAX, galerie, formulaire résultat.
 */
(function () {
    'use strict';

    var cfg     = window.shlTerrain || {};
    var gps     = null;     // { lat, lng, accuracy }
    var gpsWatchId = null;

    /* ══════════════════════════════════════════════════════
       GPS
    ══════════════════════════════════════════════════════ */
    function startGPS() {
        if (!navigator.geolocation) {
            setGpsStatus('error', 'GPS non supporté par ce navigateur');
            return;
        }
        setGpsStatus('acquiring', 'Acquisition GPS en cours…');

        if (gpsWatchId !== null) { navigator.geolocation.clearWatch(gpsWatchId); }

        gpsWatchId = navigator.geolocation.watchPosition(
            function (pos) {
                gps = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy };
                var text = '📍 ' + gps.lat.toFixed(5) + ', ' + gps.lng.toFixed(5)
                         + ' (±' + Math.round(gps.accuracy) + ' m)';
                setGpsStatus('ok', text);
            },
            function (err) {
                var msgs = {
                    1: 'Autorisation GPS refusée – activez la localisation dans les réglages.',
                    2: 'Position GPS introuvable.',
                    3: 'Délai GPS expiré – réessayez.'
                };
                setGpsStatus('error', msgs[err.code] || 'Erreur GPS');
            },
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 30000 }
        );
    }

    function setGpsStatus(status, text) {
        var bar   = document.getElementById('shl-gps-status');
        var label = document.getElementById('shl-gps-text');
        if (!bar || !label) { return; }
        bar.className   = 'shl-gps-' + status;
        label.textContent = text;
    }

    /* ══════════════════════════════════════════════════════
       CAMERA + GALERIE
    ══════════════════════════════════════════════════════ */
    function bindFileInputs() {
        ['shl-camera-input', 'shl-gallery-input'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) { return; }
            el.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    processPhoto(this.files[0]);
                }
                this.value = '';   // reset pour permettre la même photo
            });
        });
    }

    function processPhoto(file) {
        if (!file) { return; }
        showUploadStatus('processing', '⏳ Compression en cours…');

        // Compression via canvas (max 1920px, JPEG q=0.85)
        compressImage(file, 1920, 0.85, function (blob) {
            uploadPhoto(blob, file.name);
        });
    }

    function compressImage(file, maxPx, quality, callback) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var w = img.width, h = img.height;
                if (w > maxPx) { h = Math.round(h * maxPx / w); w = maxPx; }
                var canvas = document.createElement('canvas');
                canvas.width  = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(function (blob) { callback(blob); }, 'image/jpeg', quality);
            };
            img.onerror = function () { callback(file); }; // fallback sans compression
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function uploadPhoto(blob, originalName) {
        showUploadStatus('processing', '⏳ Envoi en cours…');

        var form = new FormData();
        form.append('action',  'shl_terrain_upload');
        form.append('nonce',   cfg.nonce);
        form.append('token',   cfg.token);
        form.append('photo',   blob, originalName || ('photo_' + Date.now() + '.jpg'));

        if (gps) {
            form.append('lat',      gps.lat);
            form.append('lng',      gps.lng);
            form.append('accuracy', gps.accuracy);
        }

        var obsType = document.querySelector('input[name="obs_type_photo"]:checked');
        if (obsType) { form.append('obs_type', obsType.value); }

        var photoComment = document.getElementById('shl-photo-comment');
        if (photoComment && photoComment.value.trim()) {
            form.append('comment', photoComment.value.trim());
        }

        fetch(cfg.ajax, { method: 'POST', body: form })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showUploadStatus('success', '✅ Photo envoyée !');
                    addPhotoToGallery(res.data);
                    if (photoComment) { photoComment.value = ''; }
                    // Décocher le type
                    var checked = document.querySelector('input[name="obs_type_photo"]:checked');
                    if (checked) { checked.checked = false; }
                    // Increment counter
                    cfg.photoCount++;
                    updatePhotoCount(cfg.photoCount);
                    setTimeout(function () { hideUploadStatus(); }, 3000);
                } else {
                    showUploadStatus('error', '❌ ' + (res.data || 'Erreur inconnue'));
                }
            })
            .catch(function (err) {
                console.error(err);
                showUploadStatus('error', '❌ Erreur réseau – vérifiez votre connexion');
            });
    }

    function showUploadStatus(type, msg) {
        var el = document.getElementById('shl-upload-status');
        if (!el) { return; }
        el.className = 'shl-upload-status shl-status-' + type;
        el.textContent = msg;
        el.style.display = 'block';
    }

    function hideUploadStatus() {
        var el = document.getElementById('shl-upload-status');
        if (el) { el.style.display = 'none'; }
    }

    function updatePhotoCount(n) {
        var el = document.getElementById('shl-photo-count');
        if (el) { el.textContent = n + ' photo(s)'; }
    }

    /* ══════════════════════════════════════════════════════
       GALERIE : ajouter une nouvelle photo
    ══════════════════════════════════════════════════════ */
    function addPhotoToGallery(data) {
        var gallery = document.getElementById('shl-photo-gallery');
        if (!gallery) { return; }

        // Masquer le message "aucune photo"
        var empty = document.getElementById('shl-gallery-empty');
        if (empty) { empty.style.display = 'none'; }

        // Construire la carte
        var item = document.createElement('div');
        item.className = 'shl-gallery-item shl-gallery-new';
        item.dataset.obsId = data.id;

        var html = '<a href="' + escHtml(data.url) + '" target="_blank" rel="noopener">'
                 + '<img src="' + escHtml(data.url) + '" alt="Observation terrain" loading="lazy">'
                 + '</a>';

        if (data.lat && data.lng) {
            var mapsUrl = 'https://www.openstreetmap.org/?mlat=' + data.lat + '&mlon=' + data.lng + '&zoom=17';
            html += '<a href="' + escHtml(mapsUrl) + '" target="_blank" rel="noopener" class="shl-gallery-gps">'
                  + '📍 ' + parseFloat(data.lat).toFixed(5) + ', ' + parseFloat(data.lng).toFixed(5)
                  + '</a>';
        }

        html += '<button class="shl-gallery-delete" data-obs-id="' + escHtml(String(data.id)) + '" title="Supprimer">✕</button>';
        item.innerHTML = html;

        // Insérer en premier
        gallery.insertBefore(item, gallery.firstChild);
    }

    /* ══════════════════════════════════════════════════════
       GALERIE : supprimer une photo
    ══════════════════════════════════════════════════════ */
    function bindDeleteButtons() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.shl-gallery-delete');
            if (!btn) { return; }
            if (!confirm('Supprimer cette photo ?')) { return; }

            var obsId = btn.dataset.obsId;
            var card  = btn.closest('.shl-gallery-item');

            var form = new FormData();
            form.append('action',  'shl_terrain_delete_photo');
            form.append('nonce',   cfg.nonce);
            form.append('token',   cfg.token);
            form.append('obs_id',  obsId);

            fetch(cfg.ajax, { method: 'POST', body: form })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        if (card) { card.remove(); }
                        cfg.photoCount = Math.max(0, cfg.photoCount - 1);
                        updatePhotoCount(cfg.photoCount);
                        if (cfg.photoCount === 0) {
                            var gallery = document.getElementById('shl-photo-gallery');
                            if (gallery) {
                                gallery.innerHTML = '<p id="shl-gallery-empty" class="shl-gallery-empty">Aucune photo pour l\'instant. Prenez votre première photo !</p>';
                            }
                        }
                    } else {
                        alert(res.data || 'Erreur lors de la suppression.');
                    }
                })
                .catch(function () { alert('Erreur réseau.'); });
        });
    }

    /* ══════════════════════════════════════════════════════
       FORMULAIRE RÉSULTAT
    ══════════════════════════════════════════════════════ */
    function bindResultForm() {
        var form = document.getElementById('shl-result-form');
        if (!form) { return; }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var obsType = form.querySelector('input[name="obs_type"]:checked');
            if (!obsType) {
                showNotice('error', 'Veuillez sélectionner un type d\'observation.');
                return;
            }

            var submitBtn = document.getElementById('shl-result-submit');
            var submitText= document.getElementById('shl-submit-text');

            submitBtn.disabled = true;
            if (submitText) { submitText.textContent = 'Envoi en cours…'; }

            var fd = new FormData();
            fd.append('action',   'shl_terrain_submit_obs');
            fd.append('nonce',    cfg.nonce);
            fd.append('token',    cfg.token);
            fd.append('obs_type', obsType.value);

            var comment = form.querySelector('textarea[name="comment"]');
            if (comment && comment.value.trim()) { fd.append('comment', comment.value.trim()); }

            if (gps) { fd.append('lat', gps.lat); fd.append('lng', gps.lng); }

            fetch(cfg.ajax, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        form.style.display = 'none';
                        showNotice('success', res.data.message || 'Observation enregistrée !');
                    } else {
                        showNotice('error', res.data || 'Erreur lors de l\'envoi.');
                        submitBtn.disabled = false;
                        if (submitText) { submitText.textContent = 'Envoyer l\'observation'; }
                    }
                })
                .catch(function () {
                    showNotice('error', 'Erreur réseau – vérifiez votre connexion.');
                    submitBtn.disabled = false;
                    if (submitText) { submitText.textContent = 'Envoyer l\'observation'; }
                });
        });
    }

    function showNotice(type, msg) {
        var el = document.getElementById('shl-result-notice');
        if (!el) { return; }
        el.className = 'shl-notice shl-notice-' + type;
        el.textContent = msg;
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ══════════════════════════════════════════════════════
       RADIOS : retour visuel tactile
    ══════════════════════════════════════════════════════ */
    function bindRadioFeedback() {
        document.addEventListener('change', function (e) {
            if (!e.target.matches('input[type="radio"]')) { return; }
            var group = e.target.getAttribute('name');
            document.querySelectorAll('input[name="' + group + '"]').forEach(function (inp) {
                inp.closest('.shl-obs-radio').classList.toggle('selected', inp.checked);
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       UTILITAIRES
    ══════════════════════════════════════════════════════ */
    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ══════════════════════════════════════════════════════
       INIT
    ══════════════════════════════════════════════════════ */
    function init() {
        startGPS();
        bindFileInputs();
        bindDeleteButtons();
        bindResultForm();
        bindRadioFeedback();

        // Bouton rafraîchir GPS
        var refreshBtn = document.getElementById('shl-refresh-gps');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                if (gpsWatchId !== null) { navigator.geolocation.clearWatch(gpsWatchId); gpsWatchId = null; }
                startGPS();
            });
        }

        // Appliquer couleur CSS dynamique
        if (cfg.color) {
            document.documentElement.style.setProperty('--t-primary', cfg.color);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
