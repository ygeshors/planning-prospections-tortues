<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables disponibles : $reg, $slot, $observations, $nonce, $ajax_url, $token, $type_labels
$color          = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$terrain_url    = SHL_TORTUES_PLUGIN_URL . 'assets/';
$date_formatted = date_i18n( 'l d F Y', strtotime( $slot->date ) );
$type_label     = $type_labels[ $slot->type_prospect ] ?? $slot->type_prospect;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="theme-color" content="#0d1b2a">
  <title>Terrain – <?php echo esc_html( $slot->zone_name ); ?></title>
  <link rel="stylesheet" href="<?php echo esc_url( $terrain_url ); ?>css/terrain.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    .shl-track-start{width:100%;background:#27ae60;color:#fff;border:none;padding:16px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .shl-track-stop{width:100%;background:#e05555;color:#fff;border:none;padding:14px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-top:12px}
    .shl-track-restart-btn{display:block;width:100%;background:transparent;color:var(--t-muted,#8a9ab0);border:1px solid currentColor;padding:10px;border-radius:8px;font-size:13px;cursor:pointer;margin-top:8px}
    .shl-track-recording-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
    .shl-track-pulse{width:14px;height:14px;border-radius:50%;background:#e05555;flex-shrink:0;animation:shlPulse 1.2s ease-in-out infinite}
    @keyframes shlPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.5);opacity:.6}}
    .shl-track-live-stats,.shl-track-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:10px 0}
    .shl-track-stats{grid-template-columns:repeat(2,1fr)}
    .shl-track-stat{background:rgba(255,255,255,.07);border-radius:10px;padding:12px;text-align:center}
    .shl-track-stat-val{display:block;font-size:20px;font-weight:800;color:#fff}
    .shl-track-stat-lbl{display:block;font-size:11px;color:var(--t-muted,#8a9ab0);margin-top:2px}
    .shl-track-done-badge{display:flex;align-items:center;gap:8px;background:rgba(39,174,96,.15);border:1px solid #27ae60;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#27ae60;font-weight:600}
    #shl-track-map-live,#shl-track-map-saved,#shl-track-map-existing{border-radius:10px;overflow:hidden;background:#1a2f45}
    .shl-track-saved-banner{text-align:center;padding:14px;background:rgba(39,174,96,.15);border:1px solid #27ae60;border-radius:10px;color:#27ae60;font-weight:700;margin-bottom:12px}
  </style>
</head>
<body class="shl-terrain-body">

<!-- En-tête -->
<header class="shl-terrain-header">
  <div class="shl-terrain-logo">
    <img src="https://ashl.fr/wp-content/uploads/2025/03/Copie-de-Copie-de-Sans-titre-297-x-210-mm-6-300x212.png" alt="SHL" style="height:38px;width:auto;display:block" onerror="this.outerHTML='🐢'">
  </div>
  <div class="shl-terrain-header-info">
    <h1><?php echo esc_html( $slot->zone_name ); ?></h1>
    <p><?php echo esc_html( $slot->commune ); ?> · <?php echo esc_html( $date_formatted ); ?></p>
    <span class="shl-terrain-type-badge"><?php echo esc_html( $type_label ); ?></span>
  </div>
  <div class="shl-terrain-volunteer">
    <span class="shl-terrain-avatar"><?php echo esc_html( mb_substr( $reg->firstname, 0, 1 ) ); ?></span>
    <span class="shl-terrain-name"><?php echo esc_html( $reg->firstname ); ?></span>
  </div>
</header>

<!-- Météo du jour -->
<?php if ( $weather ) : ?>
<div style="background:linear-gradient(135deg,#0d4f6e,#1a7fa8);padding:8px 16px;display:flex;align-items:center;gap:12px;font-size:13px;color:#fff">
  <span style="font-size:22px"><?php echo $weather['icon']; // phpcs:ignore ?></span>
  <span style="font-weight:600"><?php echo esc_html( $weather['label'] ); ?></span>
  <span style="opacity:.8"><?php echo esc_html( $weather['tmax'] . '° / ' . $weather['tmin'] . '°' ); ?></span>
  <?php if ( $weather['wind'] > 0 ) : ?>
    <span style="opacity:.8">💨 <?php echo esc_html( $weather['wind'] ); ?> km/h</span>
  <?php endif; ?>
  <?php if ( $weather['rain'] > 0 ) : ?>
    <span style="opacity:.8">🌧 <?php echo esc_html( $weather['rain'] ); ?> mm</span>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ( ! empty( $zone_geojson ) ) : ?>
<!-- Zone de prospection -->
<section class="shl-section" style="padding-bottom:0">
  <div class="shl-section-title" id="shl-zone-toggle" style="cursor:pointer;user-select:none" onclick="shlToggleZone()">
    <span class="shl-section-icon">🏖️</span>
    <h2 style="flex:1">Zone de prospection</h2>
    <span id="shl-zone-arrow" style="font-size:18px;color:#8a9ab0;transition:transform .2s">▼</span>
  </div>
  <div id="shl-zone-panel" style="display:none">
    <div id="shl-zone-map-terrain" style="height:250px;border-radius:0 0 10px 10px;overflow:hidden"></div>
  </div>
</section>
<script>
var shlZoneMapInit = false;
function shlToggleZone() {
  var panel = document.getElementById('shl-zone-panel');
  var arrow = document.getElementById('shl-zone-arrow');
  var open = panel.style.display !== 'none';
  panel.style.display = open ? 'none' : 'block';
  arrow.style.transform = open ? '' : 'rotate(180deg)';
  if (!open && !shlZoneMapInit) {
    shlZoneMapInit = true;
    setTimeout(function() {
      var gj = <?php echo $zone_geojson; // JSON already validated server-side ?>;
      var zmap = L.map('shl-zone-map-terrain', { zoomControl: true, attributionControl: false });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(zmap);
      var zlayer = L.geoJSON(gj, {
        style: function(f) {
          var t = f.geometry ? f.geometry.type : '';
          return (t === 'Polygon' || t === 'MultiPolygon')
            ? {color:'<?php echo esc_js(get_option("shl_tortues_primary_color","#2E86AB")); ?>',fillOpacity:.2,weight:3}
            : {color:'#e8a23a',weight:4};
        }
      }).addTo(zmap);
      <?php if ( ! empty( $slot->latitude ) && ! empty( $slot->longitude ) ) : ?>
      L.circleMarker([<?php echo esc_js($slot->latitude); ?>,<?php echo esc_js($slot->longitude); ?>],
        {radius:9,fillColor:'#27ae60',color:'#fff',weight:2,fillOpacity:1}).bindTooltip('📌 RDV').addTo(zmap);
      <?php endif; ?>
      if (zlayer.getLayers().length > 0) {
        zmap.fitBounds(zlayer.getBounds(), {padding:[20,20]});
      }
    }, 100);
  }
}
</script>
<?php endif; ?>

<!-- GPS Status -->
<div class="shl-gps-bar">
  <div id="shl-gps-status" class="shl-gps-acquiring">
    <span class="shl-gps-icon">📡</span>
    <span id="shl-gps-text">Acquisition GPS en cours…</span>
  </div>
  <button id="shl-refresh-gps" class="shl-gps-refresh" title="Rafraîchir GPS">🔄</button>
</div>

<!-- ══ SECTION GPS TRACK ══ -->
<section class="shl-section" id="shl-track-section">
  <div class="shl-section-title">
    <span class="shl-section-icon">🗺️</span>
    <h2>Tracé GPS</h2>
  </div>

  <?php if ( $existing_track ) : ?>
  <!-- Tracé déjà enregistré -->
  <div id="shl-track-done">
    <div class="shl-track-done-badge">✅ Tracé enregistré</div>
    <div class="shl-track-stats">
      <?php
      $dist_km = $existing_track->distance_m >= 1000
        ? number_format( $existing_track->distance_m / 1000, 2 ) . ' km'
        : round( $existing_track->distance_m ) . ' m';
      $dur_h   = floor( $existing_track->duration_s / 3600 );
      $dur_m   = floor( ( $existing_track->duration_s % 3600 ) / 60 );
      $dur_fmt = $dur_h > 0 ? $dur_h . 'h' . str_pad( $dur_m, 2, '0', STR_PAD_LEFT ) : $dur_m . 'min';
      ?>
      <div class="shl-track-stat">
        <span class="shl-track-stat-val"><?php echo esc_html( $dist_km ); ?></span>
        <span class="shl-track-stat-lbl">Distance</span>
      </div>
      <div class="shl-track-stat">
        <span class="shl-track-stat-val"><?php echo esc_html( $dur_fmt ); ?></span>
        <span class="shl-track-stat-lbl">Durée</span>
      </div>
    </div>
    <div id="shl-track-map-existing" style="height:220px;margin-top:10px"></div>
    <button id="shl-track-restart-btn" class="shl-track-restart-btn">🔄 Enregistrer un nouveau tracé</button>
  </div>
  <?php endif; ?>

  <!-- État : prêt à démarrer -->
  <div id="shl-track-idle" <?php echo $existing_track ? 'style="display:none"' : ''; ?>>
    <p style="font-size:13px;color:var(--t-muted,#8a9ab0);margin:0 0 14px;line-height:1.6">
      Démarrez avant votre prospection. Votre tracé sera enregistré et visible dans votre espace bénévole.
    </p>
    <button id="shl-track-start-btn" class="shl-track-start">
      ▶ Démarrer le tracé GPS
    </button>
  </div>

  <!-- État : enregistrement en cours -->
  <div id="shl-track-recording" style="display:none">
    <div class="shl-track-recording-header">
      <div class="shl-track-pulse"></div>
      <span style="font-weight:700;color:#e05555;font-size:15px">Enregistrement en cours</span>
    </div>
    <div class="shl-track-live-stats">
      <div class="shl-track-stat"><span class="shl-track-stat-val" id="shl-track-timer">00:00</span><span class="shl-track-stat-lbl">Durée</span></div>
      <div class="shl-track-stat"><span class="shl-track-stat-val" id="shl-track-dist-live">0 m</span><span class="shl-track-stat-lbl">Distance</span></div>
      <div class="shl-track-stat"><span class="shl-track-stat-val" id="shl-track-pts">0</span><span class="shl-track-stat-lbl">Points GPS</span></div>
    </div>
    <div id="shl-track-map-live" style="height:220px;margin:12px 0"></div>
    <button id="shl-track-stop-btn" class="shl-track-stop">⏹ Terminer et enregistrer le tracé</button>
  </div>

  <!-- État : tracé sauvegardé -->
  <div id="shl-track-saved-result" style="display:none">
    <div class="shl-track-saved-banner">✅ Tracé enregistré !</div>
    <div class="shl-track-stats" id="shl-track-final-stats"></div>
    <div id="shl-track-map-saved" style="height:220px;margin-top:10px"></div>
  </div>

  <div id="shl-track-status" style="font-size:12px;color:var(--t-muted,#8a9ab0);margin-top:8px;min-height:16px"></div>
</section>

<!-- ══ SECTION 1 : Photos terrain ══ -->
<section class="shl-section">
  <div class="shl-section-title">
    <span class="shl-section-icon">📸</span>
    <h2>Photos terrain</h2>
    <span class="shl-photo-count" id="shl-photo-count"><?php echo count( $observations ); ?> photo(s)</span>
  </div>

  <!-- Boutons capture -->
  <div class="shl-capture-btns">
    <label class="shl-capture-btn shl-capture-camera" for="shl-camera-input">
      📷 Appareil photo
    </label>
    <input type="file" id="shl-camera-input" accept="image/*" capture="environment" style="display:none">

    <label class="shl-capture-btn shl-capture-gallery" for="shl-gallery-input">
      🖼️ Galerie
    </label>
    <input type="file" id="shl-gallery-input" accept="image/*" style="display:none">
  </div>

  <!-- Étiquette de la photo (annotation facultative) -->
  <div class="shl-photo-obs-type">
    <p class="shl-field-label" style="margin-bottom:2px">🏷️ Étiqueter cette photo <span style="font-weight:400;opacity:.7">(optionnel)</span></p>
    <p style="font-size:11px;color:var(--t-muted,#8a9ab0);margin:0 0 10px;font-style:italic">Pour classer vos photos — pas le bilan final de la sortie</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
      <label style="cursor:pointer"><input type="radio" name="obs_type_photo" value="none"      style="display:none" class="shl-tag-radio"><span class="shl-photo-tag">✅ RAS</span></label>
      <label style="cursor:pointer"><input type="radio" name="obs_type_photo" value="suspect"   style="display:none" class="shl-tag-radio"><span class="shl-photo-tag">⚠️ Suspect</span></label>
      <label style="cursor:pointer"><input type="radio" name="obs_type_photo" value="confirmed" style="display:none" class="shl-tag-radio"><span class="shl-photo-tag">🐢 Trace</span></label>
      <label style="cursor:pointer"><input type="radio" name="obs_type_photo" value="other"     style="display:none" class="shl-tag-radio"><span class="shl-photo-tag">👁️ Autre</span></label>
    </div>
    <textarea id="shl-photo-comment" class="shl-textarea" placeholder="Description de cette photo (optionnel)…" rows="2"></textarea>
  </div>
  <style>
    .shl-photo-tag{display:inline-block;padding:6px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);font-size:13px;font-weight:600;cursor:pointer;transition:.15s}
    .shl-tag-radio:checked+.shl-photo-tag{background:var(--t-primary,#2E86AB);border-color:var(--t-primary,#2E86AB);color:#fff}
  </style>

  <!-- Statut upload -->
  <div id="shl-upload-status" class="shl-upload-status" style="display:none"></div>

  <!-- Galerie des photos déjà envoyées -->
  <div id="shl-photo-gallery" class="shl-photo-gallery">
    <?php if ( empty( $observations ) ) : ?>
      <p id="shl-gallery-empty" class="shl-gallery-empty">Aucune photo pour l'instant. Prenez votre première photo !</p>
    <?php else : ?>
      <?php foreach ( $observations as $obs ) : ?>
        <?php if ( $obs->photo_url ) : ?>
        <div class="shl-gallery-item" data-obs-id="<?php echo esc_attr( $obs->id ); ?>">
          <a href="<?php echo esc_url( $obs->photo_url ); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url( $obs->photo_url ); ?>" alt="Observation terrain" loading="lazy">
          </a>
          <?php if ( $obs->obs_type ) : ?>
            <span class="shl-gallery-label shl-obs-<?php echo esc_attr( $obs->obs_type ); ?>">
              <?php echo esc_html( SHL_Tortues_Observations::type_label( $obs->obs_type ) ); ?>
            </span>
          <?php endif; ?>
          <?php if ( $obs->latitude && $obs->longitude ) : ?>
            <a href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr( $obs->latitude ); ?>&mlon=<?php echo esc_attr( $obs->longitude ); ?>&zoom=17"
               target="_blank" rel="noopener" class="shl-gallery-gps">
              📍 <?php echo esc_html( number_format( (float)$obs->latitude, 5 ) . ', ' . number_format( (float)$obs->longitude, 5 ) ); ?>
            </a>
          <?php endif; ?>
          <?php if ( $obs->comment ) : ?>
            <p class="shl-gallery-comment"><?php echo esc_html( $obs->comment ); ?></p>
          <?php endif; ?>
          <button class="shl-gallery-delete" data-obs-id="<?php echo esc_attr( $obs->id ); ?>" title="Supprimer cette photo">✕</button>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ══ SECTION 2 : Bilan final de la prospection ══ -->
<section class="shl-section">
  <div class="shl-section-title">
    <span class="shl-section-icon">📋</span>
    <h2>Bilan final</h2>
  </div>
  <p style="font-size:13px;color:var(--t-muted,#8a9ab0);margin:0 0 16px;line-height:1.5;border-left:3px solid var(--t-primary,#2E86AB);padding-left:10px">
    Résumé global de votre prospection — à remplir <strong style="color:#fff">une seule fois</strong> en fin de sortie.
  </p>

  <form id="shl-result-form" class="shl-result-form">
    <p class="shl-field-label">Qu'avez-vous observé sur l'ensemble du parcours ? <span class="shl-required">*</span></p>
    <div class="shl-obs-radio-grid shl-obs-radio-grid-lg">
      <label class="shl-obs-radio shl-obs-radio-lg">
        <input type="radio" name="obs_type" value="none" required>
        <span class="shl-obs-icon">✅</span>
        <span class="shl-obs-text">Aucune trace</span>
      </label>
      <label class="shl-obs-radio shl-obs-radio-lg">
        <input type="radio" name="obs_type" value="suspect">
        <span class="shl-obs-icon">⚠️</span>
        <span class="shl-obs-text">Trace suspecte</span>
      </label>
      <label class="shl-obs-radio shl-obs-radio-lg">
        <input type="radio" name="obs_type" value="confirmed">
        <span class="shl-obs-icon">🐢</span>
        <span class="shl-obs-text">Trace confirmée !</span>
      </label>
      <label class="shl-obs-radio shl-obs-radio-lg">
        <input type="radio" name="obs_type" value="other">
        <span class="shl-obs-icon">👁️</span>
        <span class="shl-obs-text">Autre observation</span>
      </label>
    </div>

    <!-- Heures réelles (valorisation bénévole) -->
    <div class="shl-time-row">
      <div class="shl-form-field">
        <label class="shl-field-label">⏰ Heure de début réelle</label>
        <input type="time" name="actual_time_start" class="shl-time-input" id="shl-time-start"
               value="<?php echo esc_attr( $reg->actual_time_start ?: substr( $slot->time_start, 0, 5 ) ); ?>">
      </div>
      <div class="shl-form-field">
        <label class="shl-field-label">⏰ Heure de fin réelle</label>
        <input type="time" name="actual_time_end" class="shl-time-input" id="shl-time-end"
               value="<?php echo esc_attr( $reg->actual_time_end ?: ( $slot->time_end ? substr( $slot->time_end, 0, 5 ) : '' ) ); ?>">
      </div>
    </div>

    <div class="shl-form-field">
      <label class="shl-field-label">Commentaire terrain</label>
      <textarea name="comment" class="shl-textarea" rows="4"
        placeholder="Décrivez ce que vous avez observé : position de la trace, taille, état, météo, faune associée…"></textarea>
    </div>

    <div id="shl-result-notice" class="shl-notice" style="display:none"></div>

    <button type="submit" class="shl-submit-btn" id="shl-result-submit">
      <span id="shl-submit-icon">📤</span>
      <span id="shl-submit-text">Envoyer l'observation</span>
    </button>
  </form>
</section>

<!-- ══ SECTION 3 : Infos du créneau ══ -->
<section class="shl-section shl-section-info">
  <div class="shl-section-title"><span class="shl-section-icon">📍</span><h2>Informations créneau</h2></div>
  <div class="shl-info-list">
    <div class="shl-info-item"><span>📅</span><span><?php echo esc_html( $date_formatted ); ?></span></div>
    <div class="shl-info-item"><span>⏰</span><span><?php echo esc_html( substr( $slot->time_start, 0, 5 ) ); ?><?php if ( $slot->time_end ) echo ' → ' . esc_html( substr( $slot->time_end, 0, 5 ) ); ?></span></div>
    <?php if ( $slot->meeting_point ) : ?>
    <div class="shl-info-item"><span>📌</span><span><?php echo esc_html( $slot->meeting_point ); ?></span></div>
    <?php endif; ?>
    <?php if ( $slot->referent ) : ?>
    <div class="shl-info-item"><span>🧭</span><span>Référent : <?php echo esc_html( $slot->referent ); ?></span></div>
    <?php endif; ?>
    <?php if ( $slot->latitude && $slot->longitude ) : ?>
    <div class="shl-info-item">
      <span>🗺️</span>
      <a href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr( $slot->latitude ); ?>&mlon=<?php echo esc_attr( $slot->longitude ); ?>&zoom=16"
         target="_blank" rel="noopener" class="shl-map-link">
        Voir le point de RDV sur la carte
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php
  $consignes = $slot->instructions ?: get_option( 'shl_tortues_general_instructions', '' );
  if ( $consignes ) :
  ?>
  <div class="shl-consignes">
    <h3>📋 Consignes</h3>
    <p><?php echo nl2br( esc_html( $consignes ) ); ?></p>
  </div>
  <?php endif; ?>
</section>

<!-- Contacts urgence -->
<section class="shl-section shl-section-contacts">
  <div class="shl-section-title"><span class="shl-section-icon">📞</span><h2>Contacts</h2></div>
  <div class="shl-info-list">
    <div class="shl-info-item">
      <span>🐢</span>
      <div>
        <div style="font-size:12px;color:var(--t-muted);margin-bottom:2px">Sauvegarde Hérault Littoral</div>
        <a href="tel:0423500338" style="color:var(--t-primary);font-weight:700;font-size:17px;text-decoration:none">04 23 50 03 38</a>
      </div>
    </div>
    <div class="shl-info-item">
      <span>🆘</span>
      <div>
        <div style="font-size:12px;color:var(--t-muted);margin-bottom:2px">RTMMF – Tortue en détresse</div>
        <a href="tel:0616862686" style="color:var(--t-orange);font-weight:700;font-size:17px;text-decoration:none">06 16 86 26 86</a>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="shl-terrain-footer">
  <p>🐢 Sauvegarde Hérault Littoral – Formulaire personnel de terrain</p>
  <p>Ce lien vous est réservé – ne le partagez pas</p>
</footer>

<!-- Template galerie item (JS) -->
<script type="text/template" id="shl-gallery-item-tpl">
  <div class="shl-gallery-item" data-obs-id="{{id}}">
    <a href="{{url}}" target="_blank" rel="noopener"><img src="{{url}}" alt="Observation terrain" loading="lazy"></a>
    {{label_html}}
    {{gps_html}}
    <button class="shl-gallery-delete" data-obs-id="{{id}}" title="Supprimer">✕</button>
  </div>
</script>

<script>
window.shlTerrain = {
  ajax:  <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
  nonce: <?php echo wp_json_encode( $nonce ); ?>,
  token: <?php echo wp_json_encode( $reg->token ); ?>,
  color: <?php echo wp_json_encode( $color ); ?>,
  photoCount: <?php echo count( $observations ); ?>
};
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ── GPS Track Recording ─────────────────────────────────────────── */
(function () {
  'use strict';

  var trackCoords  = [];   // [[lat, lng], ...]
  var trackDist    = 0;    // metres
  var startTime    = null;
  var timerIv      = null;
  var watchId      = null;
  var lastLat      = null, lastLng = null;
  var startedAt    = null, endedAt = null;
  var liveMap      = null, livePoly = null, liveMarker = null;

  var elIdle   = document.getElementById('shl-track-idle');
  var elRec    = document.getElementById('shl-track-recording');
  var elSaved  = document.getElementById('shl-track-saved-result');
  var elDone   = document.getElementById('shl-track-done');
  var elTimer  = document.getElementById('shl-track-timer');
  var elDist   = document.getElementById('shl-track-dist-live');
  var elPts    = document.getElementById('shl-track-pts');
  var elStatus = document.getElementById('shl-track-status');

  var existingTrackData = <?php echo $existing_track
    ? wp_json_encode( array( 'geojson' => $existing_track->geojson, 'distance_m' => $existing_track->distance_m, 'duration_s' => $existing_track->duration_s ) )
    : 'null'; ?>;

  /* Init map for existing track */
  if (existingTrackData && document.getElementById('shl-track-map-existing')) {
    window.addEventListener('load', function () {
      initStaticMap('shl-track-map-existing', existingTrackData.geojson, '#27ae60');
    });
  }

  /* Restart button */
  var btnRestart = document.getElementById('shl-track-restart-btn');
  if (btnRestart) {
    btnRestart.addEventListener('click', function () {
      if (elDone) elDone.style.display = 'none';
      elIdle.style.display = 'block';
    });
  }

  var btnStart = document.getElementById('shl-track-start-btn');
  var btnStop  = document.getElementById('shl-track-stop-btn');
  if (!btnStart) return;

  btnStart.addEventListener('click', startRec);
  if (btnStop) btnStop.addEventListener('click', stopRec);

  /* ── Start recording ───────────────────────────────────────── */
  function startRec() {
    if (!navigator.geolocation) {
      setStatus('GPS non disponible sur cet appareil.');
      return;
    }
    trackCoords = []; trackDist = 0; lastLat = null; lastLng = null;
    startTime = Date.now();
    startedAt = hhmm(new Date());

    var tsEl = document.getElementById('shl-time-start');
    if (tsEl && !tsEl.value) tsEl.value = startedAt;

    elIdle.style.display = 'none';
    elRec.style.display  = 'block';

    /* Live map */
    setTimeout(function () {
      liveMap = L.map('shl-track-map-live', { zoomControl: true, attributionControl: false });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(liveMap);
      livePoly = L.polyline([], { color: '#e05555', weight: 4, opacity: 0.9 }).addTo(liveMap);
      liveMap.setView([43.28, 3.38], 13);
    }, 250);

    timerIv = setInterval(tickTimer, 1000);

    watchId = navigator.geolocation.watchPosition(
      onPos,
      function (err) { setStatus('GPS : ' + err.message); },
      { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
    );
    setStatus('GPS actif — restez à l\'extérieur pour une meilleure précision.');
  }

  /* ── GPS position received ─────────────────────────────────── */
  function onPos(pos) {
    var lat = pos.coords.latitude;
    var lng = pos.coords.longitude;
    var acc = pos.coords.accuracy;

    if (acc > 60) { setStatus('Précision faible (' + Math.round(acc) + 'm) — en attente…'); return; }

    if (lastLat !== null) {
      var d = haversine(lastLat, lastLng, lat, lng);
      if (d < 5) return;
      trackDist += d;
    }
    lastLat = lat; lastLng = lng;
    trackCoords.push([lat, lng]);

    if (elDist) elDist.textContent = fmtDist(trackDist);
    if (elPts)  elPts.textContent  = trackCoords.length;
    setStatus('GPS OK — précision ' + Math.round(acc) + ' m');

    if (livePoly) {
      livePoly.setLatLngs(trackCoords);
      if (!liveMarker) {
        liveMarker = L.circleMarker([lat, lng], { radius: 9, color: '#fff', fillColor: '#e05555', fillOpacity: 1, weight: 2 }).addTo(liveMap);
        liveMap.setView([lat, lng], 16);
      } else {
        liveMarker.setLatLng([lat, lng]);
        liveMap.panTo([lat, lng], { animate: true, duration: 1 });
      }
    }
  }

  /* ── Stop recording ────────────────────────────────────────── */
  function stopRec() {
    if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    clearInterval(timerIv); timerIv = null;
    endedAt = hhmm(new Date());

    var teEl = document.getElementById('shl-time-end');
    if (teEl && !teEl.value) teEl.value = endedAt;

    if (trackCoords.length < 2) {
      setStatus('Tracé trop court — marchez un peu avant d\'arrêter.');
      elRec.style.display = 'none';
      elIdle.style.display = 'block';
      return;
    }

    var durS = Math.round((Date.now() - startTime) / 1000);
    var geojson = JSON.stringify({
      type: 'LineString',
      coordinates: trackCoords.map(function (c) { return [c[1], c[0]]; })
    });

    var fd = new FormData();
    fd.append('action',      'shl_terrain_save_track');
    fd.append('nonce',       window.shlTerrain.nonce);
    fd.append('token',       window.shlTerrain.token);
    fd.append('geojson',     geojson);
    fd.append('distance_m',  Math.round(trackDist));
    fd.append('duration_s',  durS);
    fd.append('started_at',  startedAt || '');
    fd.append('ended_at',    endedAt   || '');

    fetch(window.shlTerrain.ajax, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function () {
        elRec.style.display = 'none';
        elSaved.style.display = 'block';

        var fs = document.getElementById('shl-track-final-stats');
        if (fs) fs.innerHTML =
          statCard(fmtDist(trackDist), 'Distance') +
          statCard(fmtDur(durS), 'Durée');

        setTimeout(function () { initStaticMap('shl-track-map-saved', geojson, '#2E86AB'); }, 300);
        setStatus('');
      })
      .catch(function () { setStatus('Erreur réseau — le tracé sera perdu. Réessayez.'); });
  }

  /* ── Helpers ───────────────────────────────────────────────── */
  function initStaticMap(id, geojson, color) {
    var el = document.getElementById(id);
    if (!el || typeof L === 'undefined') return;
    var data   = typeof geojson === 'string' ? JSON.parse(geojson) : geojson;
    var coords = data.coordinates.map(function (c) { return [c[1], c[0]]; });
    var map    = L.map(id, { zoomControl: false, attributionControl: false, dragging: true, scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    var poly = L.polyline(coords, { color: color || '#2E86AB', weight: 5, opacity: 0.9 }).addTo(map);
    if (coords.length > 0) {
      L.circleMarker(coords[0],                { radius: 8, fillColor: '#27ae60', color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map);
      L.circleMarker(coords[coords.length - 1],{ radius: 8, fillColor: '#e05555', color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map);
    }
    map.fitBounds(poly.getBounds(), { padding: [16, 16] });
    return map;
  }

  function tickTimer() {
    if (elTimer) elTimer.textContent = fmtDur(Math.round((Date.now() - startTime) / 1000));
  }

  function fmtDist(m) {
    return m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(2) + ' km';
  }
  function fmtDur(s) {
    var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return h > 0 ? h + 'h' + pad(m) : pad(m) + ':' + pad(sec);
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function hhmm(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }
  function setStatus(msg) { if (elStatus) elStatus.textContent = msg; }
  function statCard(val, lbl) {
    return '<div class="shl-track-stat"><span class="shl-track-stat-val">' + val + '</span><span class="shl-track-stat-lbl">' + lbl + '</span></div>';
  }

  function haversine(la1, lo1, la2, lo2) {
    var R = 6371000, r = Math.PI / 180;
    var dLa = (la2 - la1) * r, dLo = (lo2 - lo1) * r;
    var a = Math.sin(dLa / 2) * Math.sin(dLa / 2) +
            Math.cos(la1 * r) * Math.cos(la2 * r) * Math.sin(dLo / 2) * Math.sin(dLo / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
})();
</script>
<script src="<?php echo esc_url( $terrain_url ); ?>js/terrain.js"></script>

</body>
</html>
