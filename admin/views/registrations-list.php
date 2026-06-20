<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$type_labels   = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );
$status_labels = array( 'pending' => 'En attente', 'validated' => 'Validé', 'refused' => 'Refusé', 'cancelled' => 'Annulé', 'waitlist' => 'Liste d\'attente' );
$status_class  = array( 'pending' => 'shl-badge-blue', 'validated' => 'shl-badge-green', 'refused' => 'shl-badge-red', 'cancelled' => 'shl-badge-grey', 'waitlist' => 'shl-badge-orange' );
$msg_map = array(
	'deleted'         => array( 'Inscription supprimée.', 'info' ),
	'broadcast_sent'  => array( 'Message envoyé à ' . intval( $_GET['sent'] ?? 0 ) . ' bénévole(s).', 'success' ),
	'broadcast_empty' => array( 'Objet ou message vide — envoi annulé.', 'error' ),
);
$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
$cur_filters = array(
	'date_from' => isset( $filters['date_from'] ) ? $filters['date_from'] : '',
	'date_to'   => isset( $filters['date_to'] )   ? $filters['date_to']   : '',
	'status'    => isset( $filters['status'] )     ? $filters['status']    : '',
	'type'      => isset( $filters['type'] )       ? $filters['type']      : '',
	'search'    => isset( $filters['search'] )     ? $filters['search']    : '',
	'slot_id'   => isset( $filters['slot_id'] )    ? $filters['slot_id']   : '',
);
?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">👥</span>
    <div>
      <h1>Inscriptions<?php if ( $current_slot ) echo ' – ' . esc_html( $current_slot->zone_name . ' ' . date_i18n( 'd/m/Y', strtotime( $current_slot->date ) ) ); ?></h1>
      <p class="shl-subtitle"><?php echo count( $registrations ); ?> inscription(s) trouvée(s)</p>
    </div>
  </div>

  <?php if ( $msg && isset( $msg_map[ $msg ] ) ) : ?>
    <div class="notice notice-<?php echo esc_attr( $msg_map[ $msg ][1] ); ?> is-dismissible"><p><?php echo esc_html( $msg_map[ $msg ][0] ); ?></p></div>
  <?php endif; ?>

  <!-- Filtres + export -->
  <div class="shl-toolbar shl-toolbar-wrap">
    <form method="get" class="shl-filter-form">
      <input type="hidden" name="page" value="shl-tortues-registrations">
      <input type="text"   name="search"    placeholder="Rechercher…" value="<?php echo esc_attr( $cur_filters['search'] ); ?>">
      <input type="date"   name="date_from" value="<?php echo esc_attr( $cur_filters['date_from'] ); ?>">
      <span style="line-height:34px">→</span>
      <input type="date"   name="date_to"   value="<?php echo esc_attr( $cur_filters['date_to'] ); ?>">
      <select name="status">
        <option value="">Tous les statuts</option>
        <?php foreach ( $status_labels as $val => $lbl ) : ?>
          <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $cur_filters['status'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type">
        <option value="">Tous les types</option>
        <?php foreach ( $type_labels as $val => $lbl ) : ?>
          <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $cur_filters['type'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ( $cur_filters['slot_id'] ) : ?>
        <input type="hidden" name="slot_id" value="<?php echo esc_attr( $cur_filters['slot_id'] ); ?>">
      <?php endif; ?>
      <button type="submit" class="shl-btn shl-btn-outline">Filtrer</button>
      <?php if ( array_filter( $cur_filters ) ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations' ) ); ?>" class="shl-btn shl-btn-ghost">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $cur_filters, array( 'page' => 'shl-tortues-registrations', 'action' => 'export_csv' ) ), admin_url( 'admin.php' ) ), 'shl_export_csv' ) ); ?>"
       class="shl-btn shl-btn-outline">⬇ Export CSV</a>

    <?php if ( $current_slot ) : ?>
      <button type="button" class="shl-btn shl-btn-outline shl-copy-btn" data-slot="<?php echo esc_attr( $current_slot->id ); ?>">📋 Copier la liste</button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&action=broadcast&slot_id=' . $current_slot->id ) ); ?>"
         class="shl-btn shl-btn-primary">📢 Message aux inscrits</a>
    <?php endif; ?>
  </div>

  <?php if ( empty( $registrations ) ) : ?>
    <div class="shl-empty-state"><p>Aucune inscription trouvée pour ces critères.</p></div>
  <?php else : ?>
    <div class="shl-card" style="padding:0">
      <table class="shl-table">
        <thead>
          <tr>
            <th>Date prospection</th>
            <th>Plage</th>
            <th>Type</th>
            <th>Horaires réels</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Adhérent</th>
            <th>Commentaire</th>
            <th>Statut</th>
            <th>Inscription le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $registrations as $r ) : ?>
          <tr<?php echo ! empty( $r->is_hors_planning ) ? ' class="shl-row-libre"' : ''; ?>>
            <td>
              <?php echo $r->date ? esc_html( date_i18n( 'd/m/Y', strtotime( $r->date ) ) ) : '—'; ?>
              <?php if ( ! empty( $r->is_hors_planning ) ) : ?>
                <span class="shl-badge shl-badge-orange" title="Prospection hors planning" style="font-size:10px;margin-left:4px">Hors planning</span>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html( $r->zone_name ?? '—' ); ?></td>
            <td><?php echo esc_html( $type_labels[ $r->type_prospect ] ?? '—' ); ?></td>
            <td>
              <?php if ( $r->actual_time_start || $r->actual_time_end ) : ?>
                <span style="white-space:nowrap">
                  <?php echo $r->actual_time_start ? esc_html( $r->actual_time_start ) : '—'; ?>
                  <?php if ( $r->actual_time_end ) echo ' → ' . esc_html( $r->actual_time_end ); ?>
                </span>
              <?php else : ?>—<?php endif; ?>
            </td>
            <td><?php echo esc_html( $r->firstname ); ?></td>
            <td><?php echo esc_html( $r->lastname ); ?></td>
            <td><a href="mailto:<?php echo esc_attr( $r->email ); ?>"><?php echo esc_html( $r->email ); ?></a></td>
            <td><?php echo $r->phone ? esc_html( $r->phone ) : '—'; ?></td>
            <td><?php echo $r->is_member ? '✅' : '—'; ?></td>
            <td class="shl-td-comment"><?php echo $r->comment ? esc_html( $r->comment ) : '—'; ?></td>
            <td>
              <?php if ( in_array( $r->status, array( 'cancelled', 'waitlist' ), true ) ) : ?>
                <span class="shl-badge <?php echo esc_attr( $status_class[ $r->status ] ?? 'shl-badge-grey' ); ?>">
                  <?php echo esc_html( $status_labels[ $r->status ] ?? $r->status ); ?>
                </span>
              <?php else : ?>
              <select class="shl-status-select shl-status-<?php echo esc_attr( $r->status ); ?>"
                      data-id="<?php echo esc_attr( $r->id ); ?>">
                <?php foreach ( array( 'pending' => 'En attente', 'validated' => 'Validé', 'refused' => 'Refusé' ) as $val => $lbl ) : ?>
                  <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $r->status, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $r->created_at ) ) ); ?></td>
            <td class="shl-actions">
              <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shl-tortues-registrations&action=delete&id=' . $r->id ), 'shl_reg_delete_' . $r->id ) ); ?>"
                 class="shl-link shl-link-danger"
                 onclick="return confirm('Supprimer cette inscription ?')">Supprimer</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
