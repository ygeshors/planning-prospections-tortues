<?php if ( ! defined( 'ABSPATH' ) ) exit;
// Variables : $slots (array WP_Object), $color (string hex), $days (int)

$month_fr = array( 1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Aoû',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc' );
$type_labels = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );
$type_icons  = array( 'foot' => '🚶', 'drone' => '🚁', 'mixed' => '🔀' );
?>
<div class="shl-carte-wrap">

  <!-- En-tête -->
  <div class="shl-carte-header">
    <span style="font-size:28px">🗺️</span>
    <div>
      <h2>Carte des prospections</h2>
      <p>Créneaux disponibles dans les <?php echo esc_html( $days ); ?> prochains jours · cliquez pour vous inscrire</p>
    </div>
  </div>

  <!-- Corps : carte + sidebar -->
  <div class="shl-carte-body">

    <!-- Carte Leaflet -->
    <div class="shl-carte-map">
      <div id="shl-carte-map-container" class="shl-carte-map-container"></div>
    </div>

    <!-- Sidebar liste -->
    <div class="shl-carte-sidebar">
      <div class="shl-carte-sidebar-header">
        📅 <?php echo esc_html( count( $slots ) ); ?> créneau<?php echo count( $slots ) > 1 ? 'x' : ''; ?> à venir
      </div>
      <?php if ( empty( $slots ) ) : ?>
        <div style="padding:24px;text-align:center;color:#aaa;font-size:13px;font-weight:600">
          Aucun créneau prévu sur cette période.
        </div>
      <?php else : ?>
        <?php foreach ( $slots as $s ) :
          $left  = max( 0, intval( $s->places_total ) - intval( $s->places_taken ) );
          $total = intval( $s->places_total );
          $pct   = $total > 0 ? $left / $total : 0;
          $place_cls = $pct > .5 ? 'shl-carte-places-ok' : ( $pct > .2 ? 'shl-carte-places-warn' : 'shl-carte-places-low' );
          $date_d = explode( '-', $s->date );
          $date_fmt = intval($date_d[2]) . ' ' . ($month_fr[ intval($date_d[1])] ?? '') . ' ' . $date_d[0];
        ?>
        <div class="shl-carte-slot-item" data-slot-id="<?php echo esc_attr( $s->id ); ?>">
          <div class="shl-carte-slot-date"><?php echo esc_html( $date_fmt ); ?> · <?php echo esc_html( substr( $s->time_start, 0, 5 ) ); ?></div>
          <div class="shl-carte-slot-name"><?php echo esc_html( $s->zone_name ); ?></div>
          <div class="shl-carte-slot-meta">
            <span><?php echo esc_html( $s->commune ); ?></span>
            <span><?php echo $type_icons[ $s->type_prospect ] ?? ''; ?> <?php echo esc_html( $type_labels[ $s->type_prospect ] ?? $s->type_prospect ); ?></span>
            <span class="shl-carte-slot-places <?php echo esc_attr( $place_cls ); ?>"><?php echo esc_html( $left ); ?> place<?php echo $left > 1 ? 's' : ''; ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

</div>

<!-- Modal inscription (réutilise celle du calendrier si présente, sinon inline) -->
<div id="shl-carte-modal-overlay" hidden style="position:fixed;inset:0;background:rgba(15,30,46,.6);z-index:99999;display:none;align-items:center;justify-content:center;padding:16px">
  <div id="shl-carte-modal" style="background:#fff;border-radius:16px;max-width:560px;width:100%;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 24px 70px rgba(0,0,0,.28);font-family:'Nunito',system-ui,sans-serif">
    <button id="shl-carte-modal-close" style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.9);border:none;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;z-index:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.18)">×</button>
    <div id="shl-carte-modal-content"></div>
  </div>
</div>

<script>
(function() {
  var slots = <?php
    $js_slots = array();
    foreach ( $slots as $s ) {
      $left  = max( 0, intval( $s->places_total ) - intval( $s->places_taken ) );
      $js_slots[] = array(
        'id'         => intval( $s->id ),
        'zone_name'  => $s->zone_name,
        'commune'    => $s->commune,
        'date'       => $s->date,
        'time_start' => substr( $s->time_start, 0, 5 ),
        'type'       => $s->type_prospect,
        'places_left'  => $left,
        'places_total' => intval( $s->places_total ),
        'lat'        => $s->gps_lat ? floatval( $s->gps_lat ) : null,
        'lng'        => $s->gps_lng ? floatval( $s->gps_lng ) : null,
        'geojson'    => $s->geojson_zone ?: null,
      );
    }
    echo wp_json_encode( $js_slots );
  ?>;
  var color = <?php echo wp_json_encode( $color ); ?>;
  var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
  var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'shl_public_nonce' ) ); ?>;

  var typeIcon = { foot:'🚶', drone:'🚁', mixed:'🔀' };
  var MONTH_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

  /* ── Init carte ── */
  function initMap() {
    if (!window.L) { setTimeout(initMap, 200); return; }

    var map = L.map('shl-carte-map-container', { attributionControl: false, zoomControl: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    // Centrer sur l'Hérault par défaut
    map.setView([43.35, 3.5], 11);

    var bounds = L.latLngBounds();
    var hasMarkers = false;

    // Dédupliquer par zone : ne garder que le prochain créneau par zone (slots déjà triés date ASC)
    var seenZones = {};
    var firstSlotPerZone = [];
    slots.forEach(function(s) {
      var zoneKey = s.geojson || (s.lat + ',' + s.lng) || s.zone_name;
      if (!seenZones[zoneKey]) {
        seenZones[zoneKey] = true;
        firstSlotPerZone.push(s);
      }
    });

    firstSlotPerZone.forEach(function(s) {
      var lat = s.lat, lng = s.lng;
      var geoLayer = null;

      // Zone dessinée
      if (s.geojson) {
        try {
          geoLayer = L.geoJSON(JSON.parse(s.geojson), {
            style: function(f) {
              var t = f.geometry ? f.geometry.type : '';
              return (t === 'Polygon' || t === 'MultiPolygon')
                ? { color: color, fillOpacity: 0.15, weight: 2, fillColor: color }
                : { color: '#e8a23a', weight: 3 };
            }
          }).addTo(map);

          // Centroïde de la couche
          var zb = geoLayer.getBounds();
          if (zb.isValid()) {
            bounds.extend(zb);
            hasMarkers = true;
            if (!lat) { lat = zb.getCenter().lat; lng = zb.getCenter().lng; }
          }

          geoLayer.on('click', function() { openSlotDetail(s.id); });
        } catch(e) {}
      }

      // Marqueur
      if (lat && lng) {
        bounds.extend([lat, lng]);
        hasMarkers = true;

        var pct   = s.places_total > 0 ? s.places_left / s.places_total : 0;
        var mcol  = pct > .5 ? '#27ae60' : (pct > .2 ? '#e8a23a' : '#e05252');
        var mIcon = L.divIcon({
          className: '',
          html: '<div style="background:' + color + ';color:#fff;font-size:11px;font-weight:800;padding:4px 9px;border-radius:20px;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,.25);border:2px solid rgba(255,255,255,.5)">'
              + typeIcon[s.type] + ' ' + s.time_start
              + '<span style="background:' + mcol + ';color:#fff;font-size:9px;padding:1px 5px;border-radius:20px;margin-left:4px">' + s.places_left + '</span>'
              + '</div>',
          iconAnchor: [0, 0]
        });

        var marker = L.marker([lat, lng], { icon: mIcon }).addTo(map);
        marker.on('click', function() { openSlotDetail(s.id); });

        // Popup au survol
        var d = new Date(s.date + 'T00:00:00');
        var dl = d.getDate() + ' ' + MONTH_FR[d.getMonth()];
        marker.bindPopup(
          '<div style="font-family:\'Nunito\',sans-serif;min-width:180px">'
          + '<div style="font-weight:800;font-size:14px;color:#1e3040">' + s.zone_name + '</div>'
          + '<div style="font-size:12px;color:#6b7e8e;margin-top:3px;font-weight:600">'
          + dl + ' · ' + s.time_start + ' · ' + s.places_left + '/' + s.places_total + ' places</div>'
          + '<div style="margin-top:8px"><button onclick="window.shlCarteOpenSlot(' + s.id + ')" style="background:' + color + ';color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:\'Nunito\',sans-serif">S\'inscrire →</button></div>'
          + '</div>',
          { maxWidth: 220 }
        );
      }
    });

    if (hasMarkers && bounds.isValid()) {
      map.fitBounds(bounds, { padding: [20, 20], maxZoom: 14 });
    }

    /* Clic sur la sidebar */
    document.querySelectorAll('.shl-carte-slot-item').forEach(function(el) {
      el.addEventListener('click', function() {
        var id = parseInt(this.dataset.slotId);
        // Mettre le marqueur en surbrillance (simple : ouvrir la modal)
        openSlotDetail(id);
      });
    });
  }

  /* ── Ouvrir détail créneau ── */
  function openSlotDetail(slotId) {
    var overlay = document.getElementById('shl-carte-modal-overlay');
    var content = document.getElementById('shl-carte-modal-content');
    content.innerHTML = '<div style="padding:40px;text-align:center"><div class="shl-spinner"></div></div>';
    overlay.removeAttribute('hidden');
    overlay.style.display = 'flex';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) { content.innerHTML = buildDetail(res.data); }
        else { content.innerHTML = '<div style="padding:20px">Erreur lors du chargement.</div>'; }
      } catch(e) { content.innerHTML = '<div style="padding:20px">Erreur.</div>'; }
    };
    xhr.send('action=shl_get_slot_details&nonce=' + encodeURIComponent(nonce) + '&slot_id=' + slotId);
  }

  window.shlCarteOpenSlot = openSlotDetail;

  function buildDetail(s) {
    var TYPE_LABEL = { foot: 'À pied', drone: 'Drone', mixed: 'Mixte' };
    var timeHtml = s.time_start + (s.time_end ? ' → ' + s.time_end : '');
    var left = s.places_left;
    var placeCls = left === 0 ? 'places-full' : (left <= 2 ? 'places-warn' : 'places-ok');

    var h = '<div style="background:linear-gradient(135deg,' + color + ',#1a5f7a);padding:22px 24px 18px;position:relative">';
    h += '<div style="display:inline-block;background:rgba(255,255,255,.2);color:#fff;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;margin-bottom:8px">';
    h += typeIcon[s.type] + ' ' + (TYPE_LABEL[s.type] || s.type) + '</div>';
    h += '<div style="font-size:20px;font-weight:900;color:#fff;margin-bottom:3px">' + esc(s.zone_name) + '</div>';
    h += '<div style="color:rgba(255,255,255,.8);font-size:13px;font-weight:600">📍 ' + esc(s.commune) + '</div></div>';

    h += '<div style="padding:18px 22px 22px">';
    h += '<div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;font-size:13px;font-weight:600;color:#1e3040">';
    h += '<div>📅 ' + esc(s.date_formatted) + '</div>';
    h += '<div>⏰ ' + esc(timeHtml) + '</div>';
    if (s.meeting_point) h += '<div>📌 ' + esc(s.meeting_point) + '</div>';
    h += '<div class="shl-places-display ' + placeCls + '" style="font-weight:800">👥 ' + left + ' place' + (left > 1 ? 's' : '') + ' disponible' + (left > 1 ? 's' : '') + ' sur ' + s.places_total + '</div>';
    h += '</div>';

    if (s.status === 'open' && left > 0) {
      h += buildForm(s.id);
    } else {
      h += '<div style="background:#fce8e4;color:#a0412c;border-radius:10px;padding:10px 14px;font-size:13px;font-weight:700">🚫 Créneau complet</div>';
    }
    h += '</div>';
    return h;
  }

  function buildForm(slotId) {
    return '<form id="shl-carte-reg-form" onsubmit="shlCarteSubmit(event,' + slotId + ')" style="display:flex;flex-direction:column;gap:10px">'
      + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">'
      + '<div><label style="font-size:11px;font-weight:700;color:#6b7e8e;display:block;margin-bottom:3px;text-transform:uppercase">Prénom *</label>'
      + '<input name="firstname" required placeholder="Jean" style="border:1.5px solid #dce9f0;border-radius:8px;padding:9px 12px;font-size:13px;width:100%;box-sizing:border-box;font-family:\'Nunito\',sans-serif"></div>'
      + '<div><label style="font-size:11px;font-weight:700;color:#6b7e8e;display:block;margin-bottom:3px;text-transform:uppercase">Nom *</label>'
      + '<input name="lastname" required placeholder="Dupont" style="border:1.5px solid #dce9f0;border-radius:8px;padding:9px 12px;font-size:13px;width:100%;box-sizing:border-box;font-family:\'Nunito\',sans-serif"></div>'
      + '</div>'
      + '<div><label style="font-size:11px;font-weight:700;color:#6b7e8e;display:block;margin-bottom:3px;text-transform:uppercase">Email *</label>'
      + '<input name="email" type="email" required placeholder="jean@exemple.fr" style="border:1.5px solid #dce9f0;border-radius:8px;padding:9px 12px;font-size:13px;width:100%;box-sizing:border-box;font-family:\'Nunito\',sans-serif"></div>'
      + '<label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:12px;font-weight:600;color:#1e3040">'
      + '<input type="checkbox" name="accepted_rules" required style="flex-shrink:0;margin-top:3px;width:15px;height:15px">'
      + '<span>J\'ai pris connaissance des consignes de prospection et je m\'engage à les respecter.</span>'
      + '</label>'
      + '<button type="submit" style="background:linear-gradient(135deg,' + color + ',#1a6890);color:#fff;border:none;border-radius:10px;padding:12px;font-size:14px;font-weight:800;cursor:pointer;font-family:\'Nunito\',sans-serif;box-shadow:0 4px 14px rgba(46,134,171,.35)">🐢 S\'inscrire</button>'
      + '<div id="shl-carte-notice" style="display:none;border-radius:8px;padding:10px;font-size:13px;font-weight:600"></div>'
      + '</form>';
  }

  window.shlCarteSubmit = function(e, slotId) {
    e.preventDefault();
    var form    = document.getElementById('shl-carte-reg-form');
    var btn     = form.querySelector('button[type=submit]');
    var notice  = document.getElementById('shl-carte-notice');
    var inputs  = form.querySelectorAll('input,textarea');
    var params  = 'action=shl_register_slot&nonce=' + encodeURIComponent(nonce) + '&slot_id=' + slotId;
    inputs.forEach(function(inp) {
      if (inp.name && inp.type !== 'checkbox') { params += '&' + encodeURIComponent(inp.name) + '=' + encodeURIComponent(inp.value); }
      else if (inp.name && inp.type === 'checkbox' && inp.checked) { params += '&' + encodeURIComponent(inp.name) + '=1'; }
    });

    btn.disabled = true; btn.textContent = '⏳ Envoi…';
    notice.style.display = 'none';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          form.style.display = 'none';
          notice.style.cssText = 'display:block;background:#e1f4eb;color:#2a7c55;border:1px solid #a3d9be;border-radius:8px;padding:10px;font-size:13px;font-weight:700';
          notice.textContent = res.data.message;
        } else {
          notice.style.cssText = 'display:block;background:#fce8e4;color:#a0412c;border:1px solid #f5b8ad;border-radius:8px;padding:10px;font-size:13px;font-weight:700';
          notice.textContent = res.data.message || 'Une erreur est survenue.';
          btn.disabled = false; btn.textContent = '🐢 S\'inscrire';
        }
      } catch(err) { btn.disabled = false; btn.textContent = '🐢 S\'inscrire'; }
    };
    xhr.send(params);
  };

  /* ── Fermer modal ── */
  document.getElementById('shl-carte-modal-close').addEventListener('click', function() {
    var o = document.getElementById('shl-carte-modal-overlay');
    o.setAttribute('hidden', ''); o.style.display = 'none';
  });
  document.getElementById('shl-carte-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) { this.setAttribute('hidden', ''); this.style.display = 'none'; }
  });

  function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Démarrer ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMap);
  } else {
    initMap();
  }
})();
</script>
