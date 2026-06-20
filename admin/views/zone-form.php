<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$is_edit = ! is_null( $zone );
$title   = $is_edit ? 'Modifier la zone' : 'Nouvelle zone de prospection';
$v = array(
	'id'           => $is_edit ? $zone->id : 0,
	'name'         => $is_edit ? $zone->name : '',
	'commune'      => $is_edit ? $zone->commune : '',
	'description'  => $is_edit ? $zone->description : '',
	'gps_lat'      => $is_edit ? $zone->gps_lat : '',
	'gps_lng'      => $is_edit ? $zone->gps_lng : '',
	'geojson_zone' => $is_edit ? ( $zone->geojson_zone ?? '' ) : '',
	'priority'     => $is_edit ? $zone->priority : 3,
);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
<style>
#shl-zone-map { height: 400px; border-radius: 10px; border: 2px solid #d0e0ec; background: #e8f4ff; }
.shl-map-toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
.shl-map-hint { font-size: 12px; color: #666; background: #f0f6ff; border: 1px solid #c8dff0; border-radius: 6px; padding: 6px 12px; flex: 1; min-width: 200px; }
.shl-map-clear { background: #fff; color: #e05555; border: 1px solid #e05555; border-radius: 6px; padding: 6px 14px; font-size: 13px; cursor: pointer; }
.shl-map-clear:hover { background: #fdecea; }
.shl-zone-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
.shl-zone-badge.has-zone { background: #e8f4ff; color: #2E86AB; border: 1px solid #b0d4ec; }
.shl-zone-badge.no-zone  { background: #f5f5f5; color: #aaa; border: 1px solid #ddd; }
</style>

<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon"><?php echo $is_edit ? '✏️' : '🗺️'; ?></span>
    <div>
      <h1><?php echo esc_html( $title ); ?></h1>
      <p class="shl-subtitle"><a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones' ) ); ?>">← Retour à la liste</a></p>
    </div>
  </div>

  <?php if ( isset( $_GET['msg'] ) && 'missing' === sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ) : ?>
    <div class="notice notice-error is-dismissible"><p>Le nom et la commune sont obligatoires.</p></div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones&action=save' ) ); ?>" class="shl-form">
    <?php wp_nonce_field( 'shl_zone_save' ); ?>
    <input type="hidden" name="zone_id" value="<?php echo esc_attr( $v['id'] ); ?>">
    <input type="hidden" name="geojson_zone" id="shl-zone-geojson" value="<?php echo esc_attr( $v['geojson_zone'] ); ?>">

    <div class="shl-form-grid shl-form-grid-narrow">
      <div class="shl-card">
        <h2 class="shl-card-title">📍 Informations</h2>

        <div class="shl-field-row">
          <div class="shl-field-group">
            <label for="name">Nom de la plage / secteur <span class="required">*</span></label>
            <input type="text" name="name" id="name" value="<?php echo esc_attr( $v['name'] ); ?>" required placeholder="ex : Plage de la Tamarissière">
          </div>
          <div class="shl-field-group">
            <label for="commune">Commune <span class="required">*</span></label>
            <input type="text" name="commune" id="commune" value="<?php echo esc_attr( $v['commune'] ); ?>" required placeholder="ex : Agde">
          </div>
        </div>

        <div class="shl-field-group">
          <label for="description">Description</label>
          <textarea name="description" id="description" rows="4" placeholder="Caractéristiques de la zone, accès, particularités…"><?php echo esc_textarea( $v['description'] ); ?></textarea>
        </div>

        <div class="shl-field-row">
          <div class="shl-field-group">
            <label for="gps_lat">Latitude GPS (point de RDV)</label>
            <input type="text" name="gps_lat" id="gps_lat" value="<?php echo esc_attr( $v['gps_lat'] ); ?>" placeholder="43.2965">
          </div>
          <div class="shl-field-group">
            <label for="gps_lng">Longitude GPS (point de RDV)</label>
            <input type="text" name="gps_lng" id="gps_lng" value="<?php echo esc_attr( $v['gps_lng'] ); ?>" placeholder="3.4752">
          </div>
        </div>

        <div class="shl-field-group">
          <label for="priority">Niveau de priorité</label>
          <select name="priority" id="priority">
            <option value="1" <?php selected( $v['priority'], 1 ); ?>>⭐⭐⭐ Prioritaire</option>
            <option value="2" <?php selected( $v['priority'], 2 ); ?>>⭐⭐ Haute</option>
            <option value="3" <?php selected( $v['priority'], 3 ); ?>>⭐ Normale</option>
            <option value="4" <?php selected( $v['priority'], 4 ); ?>>Basse</option>
            <option value="5" <?php selected( $v['priority'], 5 ); ?>>Archive</option>
          </select>
          <p class="shl-help">Les zones prioritaires apparaissent en premier dans les sélecteurs de création de créneaux.</p>
        </div>
      </div>

      <!-- ─── Carte de délimitation ─────────────────────────────── -->
      <div class="shl-card">
        <h2 class="shl-card-title" style="display:flex;align-items:center;gap:10px">
          🗺️ Zone de prospection
          <span id="shl-zone-status" class="shl-zone-badge <?php echo $v['geojson_zone'] ? 'has-zone' : 'no-zone'; ?>">
            <?php echo $v['geojson_zone'] ? '✓ Zone tracée' : '— Non tracée'; ?>
          </span>
        </h2>
        <p class="shl-help" style="margin-bottom:12px">
          Tracez le secteur que les bénévoles devront prospecter. Ce tracé sera visible dans le formulaire terrain et l'espace bénévole.<br>
          <strong>Polygone</strong> = zone fermée · <strong>Ligne</strong> = transect linéaire le long de la plage
        </p>

        <div class="shl-map-toolbar">
          <span class="shl-map-hint">🖱️ Utilisez les outils en haut à gauche de la carte pour dessiner. Double-cliquez pour terminer le tracé.</span>
          <button type="button" class="shl-map-clear" id="shl-zone-clear">🗑️ Effacer le tracé</button>
        </div>

        <div id="shl-zone-map"></div>
      </div>
    </div>

    <div class="shl-form-actions">
      <button type="submit" class="shl-btn shl-btn-primary shl-btn-lg">💾 Enregistrer la zone</button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones' ) ); ?>" class="shl-btn shl-btn-ghost">Annuler</a>
    </div>
  </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
(function() {
  var COLOR_POLYGON = '#2E86AB';
  var COLOR_LINE    = '#e8a23a';

  var initLat = parseFloat(document.getElementById('gps_lat').value) || 43.37;
  var initLng = parseFloat(document.getElementById('gps_lng').value) || 3.50;
  var initZoom = (document.getElementById('gps_lat').value) ? 14 : 12;

  var map = L.map('shl-zone-map').setView([initLat, initLng], initZoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // Calque des formes dessinées
  var drawnItems = new L.FeatureGroup().addTo(map);

  // Outils de dessin
  var drawControl = new L.Control.Draw({
    position: 'topleft',
    edit: { featureGroup: drawnItems, poly: { allowIntersection: false } },
    draw: {
      polygon: {
        allowIntersection: false,
        showArea: true,
        shapeOptions: { color: COLOR_POLYGON, fillOpacity: 0.2, weight: 3 }
      },
      polyline: {
        shapeOptions: { color: COLOR_LINE, weight: 4 }
      },
      marker:       false,
      circle:       false,
      rectangle:    false,
      circlemarker: false
    }
  });
  map.addControl(drawControl);

  // Marqueur point de RDV (latitude/longitude existants)
  var rdvMarker = null;
  function updateRdvMarker() {
    var lat = parseFloat(document.getElementById('gps_lat').value);
    var lng = parseFloat(document.getElementById('gps_lng').value);
    if (!isNaN(lat) && !isNaN(lng)) {
      if (rdvMarker) { rdvMarker.setLatLng([lat, lng]); }
      else {
        rdvMarker = L.circleMarker([lat, lng], {
          radius: 8, fillColor: '#27ae60', color: '#fff', weight: 2, fillOpacity: 1
        }).bindTooltip('📌 Point de RDV').addTo(map);
      }
    }
  }
  document.getElementById('gps_lat').addEventListener('change', function() {
    updateRdvMarker();
    recenterIfNoZone();
  });
  document.getElementById('gps_lng').addEventListener('change', function() {
    updateRdvMarker();
    recenterIfNoZone();
  });

  function recenterIfNoZone() {
    if (drawnItems.getLayers().length === 0) {
      var lat = parseFloat(document.getElementById('gps_lat').value);
      var lng = parseFloat(document.getElementById('gps_lng').value);
      if (!isNaN(lat) && !isNaN(lng)) { map.setView([lat, lng], 14); }
    }
  }

  // Charger zone existante
  var existingRaw = document.getElementById('shl-zone-geojson').value;
  if (existingRaw) {
    try {
      var gj = JSON.parse(existingRaw);
      L.geoJSON(gj, {
        style: function(f) {
          var t = f.geometry.type;
          return t === 'Polygon' || t === 'MultiPolygon'
            ? { color: COLOR_POLYGON, fillOpacity: 0.2, weight: 3 }
            : { color: COLOR_LINE, weight: 4 };
        }
      }).eachLayer(function(l) { drawnItems.addLayer(l); });
      if (drawnItems.getLayers().length > 0) {
        map.fitBounds(drawnItems.getBounds(), { padding: [30, 30] });
      }
    } catch(e) {}
  }
  updateRdvMarker();

  // Mettre à jour l'input caché à chaque modification
  function syncGeoJSON() {
    if (drawnItems.getLayers().length === 0) {
      document.getElementById('shl-zone-geojson').value = '';
      setStatus(false);
    } else {
      document.getElementById('shl-zone-geojson').value = JSON.stringify(drawnItems.toGeoJSON());
      setStatus(true);
    }
  }

  map.on(L.Draw.Event.CREATED, function(e) {
    drawnItems.clearLayers(); // une seule forme à la fois
    drawnItems.addLayer(e.layer);
    syncGeoJSON();
  });
  map.on(L.Draw.Event.EDITED,  syncGeoJSON);
  map.on(L.Draw.Event.DELETED, syncGeoJSON);

  // Bouton effacer
  document.getElementById('shl-zone-clear').addEventListener('click', function() {
    if (!confirm('Effacer le tracé de la zone ?')) return;
    drawnItems.clearLayers();
    syncGeoJSON();
  });

  function setStatus(hasZone) {
    var el = document.getElementById('shl-zone-status');
    if (hasZone) {
      el.className = 'shl-zone-badge has-zone';
      el.textContent = '✓ Zone tracée';
    } else {
      el.className = 'shl-zone-badge no-zone';
      el.textContent = '— Non tracée';
    }
  }
})();
</script>
