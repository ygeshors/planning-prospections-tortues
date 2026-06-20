<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$obs_type_labels = array(
	'none'      => '✅ Aucune trace',
	'suspect'   => '⚠️ Trace suspecte',
	'confirmed' => '🐢 Trace confirmée',
	'other'     => '👁️ Autre observation',
	''          => '— Non renseigné',
);
$obs_type_class = array(
	'none'      => 'shl-badge-green',
	'suspect'   => 'shl-badge-orange',
	'confirmed' => 'shl-badge-blue',
	'other'     => 'shl-badge-grey',
	''          => 'shl-badge-grey',
);
$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">📸</span>
    <div>
      <h1>Observations terrain<?php if ( $current_slot ) echo ' – ' . esc_html( $current_slot->zone_name . ' ' . date_i18n( 'd/m/Y', strtotime( $current_slot->date ) ) ); ?></h1>
      <p class="shl-subtitle">Photos géolocalisées et résultats saisis par les bénévoles</p>
    </div>
  </div>

  <?php if ( 'deleted' === $msg ) : ?>
    <div class="notice notice-info is-dismissible"><p>Observation supprimée.</p></div>
  <?php endif; ?>

  <!-- Statistiques rapides -->
  <div class="shl-stats-grid" style="grid-template-columns: repeat(5, 1fr)">
    <div class="shl-stat-card shl-stat-blue">
      <div class="shl-stat-icon">📸</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats_obs['total'] ); ?></div>
      <div class="shl-stat-label">Observations totales</div>
    </div>
    <div class="shl-stat-card shl-stat-teal">
      <div class="shl-stat-icon">🖼️</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats_obs['with_photo'] ); ?></div>
      <div class="shl-stat-label">Avec photo</div>
    </div>
    <div class="shl-stat-card shl-stat-green">
      <div class="shl-stat-icon">📍</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats_obs['with_gps'] ); ?></div>
      <div class="shl-stat-label">Géolocalisées</div>
    </div>
    <div class="shl-stat-card shl-stat-orange">
      <div class="shl-stat-icon">⚠️</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats_obs['suspect'] ); ?></div>
      <div class="shl-stat-label">Traces suspectes</div>
    </div>
    <div class="shl-stat-card" style="border-color:#3d9970">
      <div class="shl-stat-icon">🐢</div>
      <div class="shl-stat-value" style="color:#3d9970"><?php echo esc_html( $stats_obs['confirmed'] ); ?></div>
      <div class="shl-stat-label">Traces confirmées</div>
    </div>
  </div>

  <?php if ( empty( $observations ) ) : ?>
    <div class="shl-empty-state">
      <p>📸 Aucune observation terrain enregistrée pour l'instant.</p>
      <p style="font-size:13px;color:#8a9ab0">Les bénévoles peuvent soumettre photos et résultats via leur lien personnel reçu par email.</p>
    </div>
  <?php else : ?>

  <!-- Vue galerie -->
  <div class="shl-obs-gallery-grid">
    <?php foreach ( $observations as $obs ) :
      $slot_label = '';
      if ( $obs->date )      { $slot_label .= date_i18n( 'd/m/Y', strtotime( $obs->date ) ) . ' – '; }
      if ( $obs->zone_name ) { $slot_label .= $obs->zone_name; }
    ?>
    <div class="shl-obs-card <?php echo $obs->photo_url ? 'shl-obs-card-photo' : ''; ?>">

      <?php if ( $obs->photo_url ) : ?>
        <div class="shl-obs-photo">
          <a href="<?php echo esc_url( $obs->photo_url ); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url( $obs->photo_url ); ?>" alt="Observation terrain" loading="lazy">
          </a>
          <?php if ( $obs->latitude && $obs->longitude ) : ?>
            <a href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr( $obs->latitude ); ?>&mlon=<?php echo esc_attr( $obs->longitude ); ?>&zoom=17"
               target="_blank" rel="noopener" class="shl-obs-photo-gps">
              📍 <?php echo esc_html( number_format( (float)$obs->latitude, 5 ) . ', ' . number_format( (float)$obs->longitude, 5 ) ); ?>
              <?php if ( $obs->accuracy ) echo '(±' . esc_html( round( $obs->accuracy ) ) . 'm)'; ?>
            </a>
          <?php endif; ?>
        </div>
      <?php else : ?>
        <div class="shl-obs-no-photo">
          <span class="shl-obs-no-photo-icon"><?php echo isset( $obs_type_labels[ $obs->obs_type ] ) ? esc_html( mb_substr( $obs_type_labels[ $obs->obs_type ], 0, 2 ) ) : '📋'; ?></span>
        </div>
      <?php endif; ?>

      <div class="shl-obs-card-body">
        <?php if ( $obs->obs_type ) : ?>
          <span class="shl-badge <?php echo esc_attr( $obs_type_class[ $obs->obs_type ] ?? 'shl-badge-grey' ); ?>">
            <?php echo esc_html( $obs_type_labels[ $obs->obs_type ] ?? $obs->obs_type ); ?>
          </span>
        <?php endif; ?>

        <p class="shl-obs-slot"><?php echo esc_html( $slot_label ); ?></p>

        <?php if ( $obs->firstname || $obs->lastname ) : ?>
          <p class="shl-obs-author">
            👤 <?php echo esc_html( trim( $obs->firstname . ' ' . $obs->lastname ) ); ?>
          </p>
        <?php endif; ?>

        <?php if ( $obs->comment ) : ?>
          <p class="shl-obs-comment"><?php echo esc_html( $obs->comment ); ?></p>
        <?php endif; ?>

        <p class="shl-obs-date"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $obs->created_at ) ) ); ?></p>

        <div class="shl-obs-actions">
          <?php if ( $obs->latitude && $obs->longitude ) : ?>
            <a href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr( $obs->latitude ); ?>&mlon=<?php echo esc_attr( $obs->longitude ); ?>&zoom=17"
               target="_blank" rel="noopener" class="shl-btn shl-btn-outline" style="font-size:11px;padding:5px 10px">🗺️ Carte</a>
          <?php endif; ?>
          <?php if ( $obs->photo_url ) : ?>
            <a href="<?php echo esc_url( $obs->photo_url ); ?>" target="_blank" rel="noopener" class="shl-btn shl-btn-outline" style="font-size:11px;padding:5px 10px">⬇ Photo</a>
          <?php endif; ?>
          <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shl-tortues-observations&action=delete&id=' . $obs->id ), 'shl_obs_delete_' . $obs->id ) ); ?>"
             class="shl-link shl-link-danger"
             style="font-size:11px"
             onclick="return confirm('Supprimer cette observation ?')">Supprimer</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>
