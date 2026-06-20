<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$priority_labels = array( 1 => '⭐⭐⭐ Prioritaire', 2 => '⭐⭐ Haute', 3 => '⭐ Normale', 4 => 'Basse', 5 => 'Archive' );
$msg_map = array(
	'saved'   => array( 'Zone enregistrée.', 'success' ),
	'deleted' => array( 'Zone supprimée.', 'info' ),
	'missing' => array( 'Nom et commune sont requis.', 'error' ),
);
$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">🗺️</span>
    <div>
      <h1>Zones de prospection</h1>
      <p class="shl-subtitle">Gérez les plages et secteurs prédéfinis</p>
    </div>
  </div>

  <?php if ( $msg && isset( $msg_map[ $msg ] ) ) : ?>
    <div class="notice notice-<?php echo esc_attr( $msg_map[ $msg ][1] ); ?> is-dismissible"><p><?php echo esc_html( $msg_map[ $msg ][0] ); ?></p></div>
  <?php endif; ?>

  <div class="shl-toolbar">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones&action=new' ) ); ?>" class="shl-btn shl-btn-primary">+ Nouvelle zone</a>
  </div>

  <?php if ( empty( $zones ) ) : ?>
    <div class="shl-empty-state">
      <p>Aucune zone définie. Commencez par créer les zones de prospection habituelles.</p>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones&action=new' ) ); ?>" class="shl-btn shl-btn-primary">Créer la première zone</a>
    </div>
  <?php else : ?>
    <div class="shl-card" style="padding:0">
      <table class="shl-table">
        <thead>
          <tr>
            <th>Priorité</th>
            <th>Nom de la plage</th>
            <th>Commune</th>
            <th>Coordonnées GPS</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $zones as $z ) : ?>
          <tr>
            <td><span class="shl-priority"><?php echo esc_html( $priority_labels[ $z->priority ] ?? $z->priority ); ?></span></td>
            <td><strong><?php echo esc_html( $z->name ); ?></strong></td>
            <td><?php echo esc_html( $z->commune ); ?></td>
            <td>
              <?php if ( $z->gps_lat && $z->gps_lng ) : ?>
                <span class="shl-gps"><?php echo esc_html( $z->gps_lat . ', ' . $z->gps_lng ); ?></span>
              <?php else : ?>—<?php endif; ?>
            </td>
            <td class="shl-td-comment"><?php echo $z->description ? esc_html( substr( $z->description, 0, 80 ) ) . ( strlen( $z->description ) > 80 ? '…' : '' ) : '—'; ?></td>
            <td class="shl-actions">
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones&action=edit&id=' . $z->id ) ); ?>" class="shl-link">Modifier</a>
              <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shl-tortues-zones&action=delete&id=' . $z->id ), 'shl_zone_delete_' . $z->id ) ); ?>"
                 class="shl-link shl-link-danger"
                 onclick="return confirm('Supprimer cette zone ?')">Supprimer</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
