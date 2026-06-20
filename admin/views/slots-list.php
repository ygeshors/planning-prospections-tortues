<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$type_labels   = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );
$status_labels = array( 'open' => 'Ouvert', 'full' => 'Complet', 'cancelled' => 'Annulé', 'done' => 'Réalisé' );
$status_class  = array( 'open' => 'shl-badge-green', 'full' => 'shl-badge-orange', 'cancelled' => 'shl-badge-red', 'done' => 'shl-badge-grey' );
$created_count = isset( $_GET['count'] ) ? max( 1, intval( wp_unslash( $_GET['count'] ) ) ) : 1;
$msg_map = array(
	'created' => array( $created_count > 1 ? $created_count . ' créneaux créés avec succès.' : 'Créneau créé avec succès.', 'success' ),
	'updated' => array( 'Créneau mis à jour.', 'success' ),
	'deleted' => array( 'Créneau supprimé.', 'info' ),
	'missing' => array( 'Champs obligatoires manquants ou plage de dates invalide.', 'error' ),
);
$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">📅</span>
    <div>
      <h1>Créneaux de prospection</h1>
      <p class="shl-subtitle">Gérez l'ensemble des créneaux planifiés</p>
    </div>
  </div>

  <?php if ( $msg && isset( $msg_map[ $msg ] ) ) : ?>
    <div class="notice notice-<?php echo esc_attr( $msg_map[ $msg ][1] ); ?> is-dismissible"><p><?php echo esc_html( $msg_map[ $msg ][0] ); ?></p></div>
  <?php endif; ?>

  <div class="shl-toolbar">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=new' ) ); ?>" class="shl-btn shl-btn-primary">+ Nouveau créneau</a>

    <form method="get" class="shl-filter-form">
      <input type="hidden" name="page" value="shl-tortues-slots">
      <select name="status">
        <option value="">Tous les statuts</option>
        <?php foreach ( $status_labels as $val => $lbl ) : ?>
          <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filter_status, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type">
        <option value="">Tous les types</option>
        <?php foreach ( $type_labels as $val => $lbl ) : ?>
          <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filter_type, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="shl-btn shl-btn-outline">Filtrer</button>
    </form>
  </div>

  <?php if ( empty( $slots ) ) : ?>
    <div class="shl-empty-state">
      <p>Aucun créneau trouvé.</p>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=new' ) ); ?>" class="shl-btn shl-btn-primary">Créer le premier créneau</a>
    </div>
  <?php else : ?>
    <div class="shl-card" style="padding:0">
      <table class="shl-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Heure</th>
            <th>Plage</th>
            <th>Commune</th>
            <th>Type</th>
            <th>Places</th>
            <th>Référent</th>
            <th>Résultat</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result_labels = array( '' => '—', 'none' => 'Aucune trace', 'suspect' => '⚠ Trace suspecte', 'confirmed' => '✅ Trace confirmée', 'other' => '👁 Autre obs.' );
          foreach ( $slots as $slot ) :
          ?>
          <tr class="<?php echo $slot->status === 'cancelled' ? 'shl-row-muted' : ''; ?>">
            <td><strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ); ?></strong></td>
            <td><?php echo esc_html( substr( $slot->time_start, 0, 5 ) ); ?><?php if ( $slot->time_end ) echo ' – ' . esc_html( substr( $slot->time_end, 0, 5 ) ); ?></td>
            <td><?php echo esc_html( $slot->zone_name ); ?></td>
            <td><?php echo esc_html( $slot->commune ); ?></td>
            <td><?php echo esc_html( $type_labels[ $slot->type_prospect ] ?? $slot->type_prospect ); ?></td>
            <td>
              <span class="shl-places <?php echo (int)$slot->places_taken >= (int)$slot->places_total ? 'shl-places-full' : ''; ?>">
                <?php echo esc_html( $slot->places_taken ); ?>/<?php echo esc_html( $slot->places_total ); ?>
              </span>
            </td>
            <td><?php echo $slot->referent ? esc_html( $slot->referent ) : '—'; ?></td>
            <td><?php echo esc_html( $result_labels[ $slot->result ] ?? '—' ); ?></td>
            <td><span class="shl-badge <?php echo esc_attr( $status_class[ $slot->status ] ?? 'shl-badge-grey' ); ?>"><?php echo esc_html( $status_labels[ $slot->status ] ?? $slot->status ); ?></span></td>
            <td class="shl-actions">
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=edit&id=' . $slot->id ) ); ?>" class="shl-link">Modifier</a>
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot->id ) ); ?>" class="shl-link">Inscrits (<?php echo esc_html( $slot->places_taken ); ?>)</a>
              <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shl-tortues-slots&action=delete&id=' . $slot->id ), 'shl_slot_delete_' . $slot->id ) ); ?>"
                 class="shl-link shl-link-danger"
                 onclick="return confirm('Supprimer ce créneau et toutes ses inscriptions ?')">Supprimer</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
