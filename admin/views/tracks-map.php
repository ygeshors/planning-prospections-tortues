<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables : $year (int), $tracks (array of objects)

// Couleurs par zone (hash stable)
function shl_zone_color( $zone_name ) {
	$palette = array( '#2E86AB', '#e05555', '#27ae60', '#e8a23a', '#9b7fd4', '#1a7fa8', '#c0392b', '#16a085', '#d35400', '#8e44ad' );
	return $palette[ abs( crc32( $zone_name ) ) % count( $palette ) ];
}

// Zones uniques pour la légende
$zones_seen = array();
foreach ( $tracks as $t ) {
	if ( ! isset( $zones_seen[ $t->zone_name ] ) ) {
		$zones_seen[ $t->zone_name ] = shl_zone_color( $t->zone_name );
	}
}

// Stats globales
$total_dist = array_sum( array_column( $tracks, 'distance_m' ) );
$total_dur  = array_sum( array_column( $tracks, 'duration_s' ) );
$nb         = count( $tracks );

$years_avail = range( intval( gmdate( 'Y' ) ), 2024 );
?>
<div class="wrap">
  <h1 style="display:flex;align-items:center;gap:12px">
    🗺️ Carte des tracés GPS
    <form method="get" style="margin:0">
      <input type="hidden" name="page" value="shl-tortues-tracks">
      <select name="year" onchange="this.form.submit()" style="font-size:14px;padding:4px 8px;border-radius:6px;border:1px solid #ccd0d4">
        <?php foreach ( $years_avail as $y ) : ?>
          <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>><?php echo esc_html( $y ); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </h1>

  <!-- Stats rapides -->
  <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <?php
    $stat_cards = array(
      array( '🗺️', $nb, 'Tracés enregistrés' ),
      array( '📏', $total_dist >= 1000 ? number_format( $total_dist / 1000, 1 ) . ' km' : round( $total_dist ) . ' m', 'Distance totale' ),
      array( '⏱️', floor( $total_dur / 3600 ) . 'h' . str_pad( floor( ( $total_dur % 3600 ) / 60 ), 2, '0', STR_PAD_LEFT ), 'Temps total bénévoles' ),
      array( '🏖️', count( $zones_seen ), 'Zones parcourues' ),
    );
    foreach ( $stat_cards as $c ) :
    ?>
    <div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:14px 20px;min-width:140px;text-align:center">
      <div style="font-size:22px"><?php echo $c[0]; // phpcs:ignore ?></div>
      <div style="font-size:22px;font-weight:800;color:#1a7fa8"><?php echo esc_html( $c[1] ); ?></div>
      <div style="font-size:11px;color:#888"><?php echo esc_html( $c[2] ); ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ( empty( $tracks ) ) : ?>
    <div style="background:#f8f9fa;border:2px dashed #ddd;border-radius:12px;padding:40px;text-align:center;color:#888">
      <div style="font-size:40px;margin-bottom:12px">🗺️</div>
      <p style="margin:0;font-size:15px">Aucun tracé GPS enregistré pour <?php echo esc_html( $year ); ?>.</p>
      <p style="margin:8px 0 0;font-size:13px">Les tracés apparaîtront ici quand les bénévoles les enregistreront depuis le formulaire terrain.</p>
    </div>
  <?php else : ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">

    <!-- Carte -->
    <div>
      <!-- Légende zones -->
      <?php if ( ! empty( $zones_seen ) ) : ?>
      <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:10px 14px;margin-bottom:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span style="font-size:12px;font-weight:600;color:#555;margin-right:4px">Zones :</span>
        <?php foreach ( $zones_seen as $zone => $col ) : ?>
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px">
          <span style="display:inline-block;width:16px;height:4px;border-radius:2px;background:<?php echo esc_attr( $col ); ?>"></span>
          <?php echo esc_html( $zone ); ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div id="shl-admin-track-map" style="height:560px;border-radius:12px;border:1px solid #ddd;overflow:hidden"></div>
      <p style="font-size:11px;color:#999;margin:6px 0 0">Cliquez sur un tracé pour voir le détail.</p>
    </div>

    <!-- Liste latérale -->
    <div style="background:#fff;border:1px solid #ddd;border-radius:12px;overflow:hidden;max-height:620px;display:flex;flex-direction:column">
      <div style="padding:12px 16px;border-bottom:1px solid #eee;font-weight:700;font-size:13px;color:#333">
        <?php echo esc_html( $nb ); ?> tracé<?php echo $nb > 1 ? 's' : ''; ?> — <?php echo esc_html( $year ); ?>
      </div>
      <div style="overflow-y:auto;flex:1">
        <?php foreach ( $tracks as $i => $t ) :
          $dist = $t->distance_m >= 1000
            ? number_format( $t->distance_m / 1000, 2 ) . ' km'
            : round( $t->distance_m ) . ' m';
          $h    = floor( $t->duration_s / 3600 );
          $m    = floor( ( $t->duration_s % 3600 ) / 60 );
          $dur  = $h > 0 ? $h . 'h' . str_pad( $m, 2, '0', STR_PAD_LEFT ) : $m . 'min';
          $col  = shl_zone_color( $t->zone_name );
        ?>
        <div id="shl-track-item-<?php echo esc_attr( $t->id ); ?>"
             onclick="shlFocusTrack(<?php echo esc_js( $t->id ); ?>)"
             style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;transition:.15s;<?php echo $i % 2 === 0 ? 'background:#fff' : 'background:#fafafa'; ?>"
             onmouseenter="this.style.background='#f0f7ff'" onmouseleave="this.style.background='<?php echo $i % 2 === 0 ? '#fff' : '#fafafa'; ?>'">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $col ); ?>;flex-shrink:0"></span>
            <span style="font-size:13px;font-weight:700;color:#222"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $t->date ) ) ); ?></span>
          </div>
          <div style="font-size:12px;color:#555;margin-bottom:2px;padding-left:18px"><?php echo esc_html( $t->zone_name ); ?> – <?php echo esc_html( $t->commune ); ?></div>
          <div style="font-size:11px;color:#888;padding-left:18px">
            <?php echo esc_html( $t->firstname . ' ' . $t->lastname ); ?> ·
            <?php echo esc_html( $dist ); ?> · <?php echo esc_html( $dur ); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- Panneau détail -->
  <div id="shl-track-detail" style="display:none;background:#fff;border:1px solid #ddd;border-radius:12px;padding:20px;margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
      <h3 id="shl-detail-title" style="margin:0;font-size:16px;color:#333"></h3>
      <button onclick="document.getElementById('shl-track-detail').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer;color:#aaa">×</button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:14px" id="shl-detail-stats"></div>
    <div id="shl-detail-map" style="height:280px;border-radius:8px;border:1px solid #eee"></div>
  </div>

  <?php endif; ?>

</div><!-- .wrap -->

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
  'use strict';

  var tracksData = <?php
    $js_tracks = array();
    foreach ( $tracks as $t ) {
      $js_tracks[] = array(
        'id'         => (int) $t->id,
        'geojson'    => $t->geojson,
        'distance_m' => (float) $t->distance_m,
        'duration_s' => (int) $t->duration_s,
        'started_at' => $t->started_at,
        'ended_at'   => $t->ended_at,
        'date'       => $t->date,
        'zone'       => $t->zone_name,
        'commune'    => $t->commune,
        'name'       => $t->firstname . ' ' . $t->lastname,
        'email'      => $t->email,
        'color'      => shl_zone_color( $t->zone_name ),
      );
    }
    echo wp_json_encode( $js_tracks );
  ?>;

  if (!tracksData.length || !document.getElementById('shl-admin-track-map')) return;

  var mainMap = L.map('shl-admin-track-map', { zoomControl: true, attributionControl: false });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mainMap);

  var bounds = L.latLngBounds();
  var polylines = {};
  var detailMap = null;

  tracksData.forEach(function(t) {
    var data   = JSON.parse(t.geojson);
    var coords = data.coordinates.map(function(c) { return [c[1], c[0]]; });
    if (!coords.length) return;

    var poly = L.polyline(coords, { color: t.color, weight: 3, opacity: 0.75 }).addTo(mainMap);
    polylines[t.id] = { poly: poly, data: t, coords: coords };
    bounds.extend(poly.getBounds());

    poly.on('click', function() { openDetail(t.id); });
    poly.on('mouseover', function() { poly.setStyle({ weight: 6, opacity: 1 }); });
    poly.on('mouseout',  function() { poly.setStyle({ weight: 3, opacity: 0.75 }); });
  });

  if (bounds.isValid()) mainMap.fitBounds(bounds, { padding: [20, 20] });

  window.shlFocusTrack = function(id) {
    var p = polylines[id];
    if (!p) return;
    mainMap.fitBounds(p.poly.getBounds(), { padding: [40, 40] });
    p.poly.setStyle({ weight: 6, opacity: 1 });
    setTimeout(function() { p.poly.setStyle({ weight: 3, opacity: 0.75 }); }, 2000);
    openDetail(id);
    document.getElementById('shl-track-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  function openDetail(id) {
    var p = polylines[id];
    if (!p) return;
    var t = p.data;

    var dist = t.distance_m < 1000 ? Math.round(t.distance_m) + ' m' : (t.distance_m/1000).toFixed(2) + ' km';
    var h = Math.floor(t.duration_s/3600), m = Math.floor((t.duration_s%3600)/60);
    var dur = h > 0 ? h+'h'+(m<10?'0':'')+m+'min' : m+'min';
    var times = (t.started_at && t.ended_at) ? t.started_at + ' → ' + t.ended_at : '—';

    document.getElementById('shl-detail-title').textContent = t.zone + ' – ' + t.commune + ' · ' + formatDate(t.date);
    document.getElementById('shl-detail-stats').innerHTML =
      card('📅', formatDate(t.date), 'Date') +
      card('👤', t.name, 'Bénévole') +
      card('📏', dist, 'Distance') +
      card('⏱️', dur, 'Durée') +
      card('🕒', times, 'Horaires') +
      card('📧', '<a href="mailto:'+t.email+'" style="font-size:11px;color:#2E86AB">'+t.email+'</a>', 'Email');

    document.getElementById('shl-track-detail').style.display = 'block';

    if (detailMap) { detailMap.remove(); detailMap = null; }
    setTimeout(function() {
      detailMap = L.map('shl-detail-map', { zoomControl: true, attributionControl: false });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(detailMap);
      var poly2 = L.polyline(p.coords, { color: t.color, weight: 5, opacity: 0.9 }).addTo(detailMap);
      L.circleMarker(p.coords[0],                { radius: 9, fillColor:'#27ae60', color:'#fff', weight:2, fillOpacity:1 }).bindPopup('Départ').addTo(detailMap);
      L.circleMarker(p.coords[p.coords.length-1],{ radius: 9, fillColor:'#e05555', color:'#fff', weight:2, fillOpacity:1 }).bindPopup('Arrivée').addTo(detailMap);
      detailMap.fitBounds(poly2.getBounds(), { padding: [20, 20] });
    }, 200);

    // Highlight row
    document.querySelectorAll('[id^="shl-track-item-"]').forEach(function(el) {
      el.style.background = '';
    });
    var row = document.getElementById('shl-track-item-' + id);
    if (row) { row.style.background = '#e8f4ff'; row.scrollIntoView({ block: 'nearest' }); }
  }

  function card(icon, val, lbl) {
    return '<div style="background:#f8f9fa;border-radius:8px;padding:10px;text-align:center">'
      + '<div style="font-size:18px">' + icon + '</div>'
      + '<div style="font-size:14px;font-weight:700;color:#333;word-break:break-all">' + val + '</div>'
      + '<div style="font-size:11px;color:#888">' + lbl + '</div></div>';
  }

  function formatDate(d) {
    if (!d) return '';
    var p = d.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
  }
})();
</script>
