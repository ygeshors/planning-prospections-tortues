<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
$type_labels   = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );
$slot_statuses = array( 'open' => 'Ouvert', 'full' => 'Complet', 'done' => 'Réalisé', 'cancelled' => 'Annulé' );
$slot_classes  = array( 'open' => 'shl-badge-green', 'full' => 'shl-badge-orange', 'done' => 'shl-badge-grey', 'cancelled' => 'shl-badge-red' );
?>

<div class="wrap shl-wrap">

<?php if ( $email_detail && $volunteer_info ) : ?>

  <!-- ── Vue détail bénévole ──────────────────────────────────────────── -->
  <div class="shl-page-header">
    <span class="shl-page-icon">👤</span>
    <div>
      <h1><?php echo esc_html( $volunteer_info['name'] ); ?></h1>
      <p class="shl-subtitle">
        <a href="mailto:<?php echo esc_attr( $volunteer_info['email'] ); ?>"><?php echo esc_html( $volunteer_info['email'] ); ?></a>
        <?php if ( $volunteer_info['phone'] ) : ?>
          &nbsp;&middot;&nbsp; <?php echo esc_html( $volunteer_info['phone'] ); ?>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-volunteers' ) ); ?>"
       class="shl-btn shl-btn-ghost">← Retour à la liste</a>
    <?php
    $att_year = intval( gmdate( 'Y' ) );
    $att_url  = SHL_Tortues_Attestation::get_url( $email_detail, $att_year );
    ?>
    <a href="<?php echo esc_url( $att_url ); ?>" target="_blank"
       class="shl-btn shl-btn-outline">📄 Attestation <?php echo esc_html( $att_year ); ?></a>
    <?php if ( $att_year > 2024 ) : ?>
    <a href="<?php echo esc_url( SHL_Tortues_Attestation::get_url( $email_detail, $att_year - 1 ) ); ?>" target="_blank"
       class="shl-btn shl-btn-ghost" style="font-size:12px">Attestation <?php echo esc_html( $att_year - 1 ); ?></a>
    <?php endif; ?>
  </div>

  <?php
  $nb_done     = 0;
  $nb_upcoming = 0;
  $nb_obs      = 0;
  $today       = date( 'Y-m-d' );
  foreach ( $history as $h ) {
      if ( $h->date < $today ) { $nb_done++; } else { $nb_upcoming++; }
      $nb_obs += intval( $h->obs_count );
  }
  $heures_estimees = $nb_done * 2;
  ?>

  <div class="shl-stats-grid" style="margin-bottom:24px">
    <div class="shl-stat-card shl-stat-blue">
      <div class="shl-stat-icon">🏁</div>
      <div class="shl-stat-value"><?php echo esc_html( $nb_done ); ?></div>
      <div class="shl-stat-label">Prospections réalisées</div>
    </div>
    <div class="shl-stat-card shl-stat-green">
      <div class="shl-stat-icon">📅</div>
      <div class="shl-stat-value"><?php echo esc_html( $nb_upcoming ); ?></div>
      <div class="shl-stat-label">À venir</div>
    </div>
    <div class="shl-stat-card shl-stat-teal">
      <div class="shl-stat-icon">📸</div>
      <div class="shl-stat-value"><?php echo esc_html( $nb_obs ); ?></div>
      <div class="shl-stat-label">Observations soumises</div>
    </div>
    <div class="shl-stat-card shl-stat-orange">
      <div class="shl-stat-icon">⏱️</div>
      <div class="shl-stat-value">~<?php echo esc_html( $heures_estimees ); ?>h</div>
      <div class="shl-stat-label">Heures bénévoles estimées</div>
    </div>
  </div>

  <?php if ( empty( $history ) ) : ?>
    <div class="shl-empty-state"><p>Aucune prospection trouvée pour ce bénévole.</p></div>
  <?php else : ?>
  <div class="shl-card" style="padding:0">
    <div class="shl-card-header" style="padding:16px 20px">
      <h2>Historique de participation</h2>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&search=' . rawurlencode( $email_detail ) ) ); ?>"
         class="shl-link">Voir toutes les inscriptions →</a>
    </div>
    <table class="shl-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Plage</th>
          <th>Commune</th>
          <th>Type</th>
          <th>Horaires réels</th>
          <th>Statut créneau</th>
          <th>Obs.</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $history as $h ) : ?>
        <tr>
          <td><strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->date ) ) ); ?></strong></td>
          <td><?php echo esc_html( $h->zone_name ); ?></td>
          <td><?php echo esc_html( $h->commune ); ?></td>
          <td><?php echo esc_html( $type_labels[ $h->type_prospect ] ?? $h->type_prospect ); ?></td>
          <td>
            <?php if ( $h->actual_time_start ) : ?>
              <?php echo esc_html( $h->actual_time_start ); ?>
              <?php if ( $h->actual_time_end ) echo ' &rarr; ' . esc_html( $h->actual_time_end ); ?>
            <?php else : ?>—<?php endif; ?>
          </td>
          <td>
            <span class="shl-badge <?php echo esc_attr( $slot_classes[ $h->slot_status ] ?? 'shl-badge-grey' ); ?>">
              <?php echo esc_html( $slot_statuses[ $h->slot_status ] ?? $h->slot_status ); ?>
            </span>
          </td>
          <td><?php echo esc_html( $h->obs_count ); ?></td>
          <td>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $h->slot_id ) ); ?>">
              Inscrits
            </a>
            <?php if ( $h->obs_count > 0 ) : ?>
              | <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-observations&slot_id=' . $h->slot_id ) ); ?>">
              Photos
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php else : ?>

  <!-- ── Vue liste bénévoles ─────────────────────────────────────────── -->
  <div class="shl-page-header">
    <span class="shl-page-icon">🧑‍🤝‍🧑</span>
    <div>
      <h1>Bénévoles</h1>
      <p class="shl-subtitle"><?php echo count( $volunteers ); ?> bénévole(s) dans la base<?php echo $search ? ' — recherche : ' . esc_html( $search ) : ''; ?></p>
    </div>
  </div>

  <div class="shl-toolbar">
    <form method="get" class="shl-filter-form">
      <input type="hidden" name="page" value="shl-tortues-volunteers">
      <input type="text" name="search" placeholder="Nom, prénom, email…" value="<?php echo esc_attr( $search ); ?>">
      <button type="submit" class="shl-btn shl-btn-outline">Rechercher</button>
      <?php if ( $search ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-volunteers' ) ); ?>" class="shl-btn shl-btn-ghost">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if ( empty( $volunteers ) ) : ?>
    <div class="shl-empty-state"><p>Aucun bénévole trouvé.</p></div>
  <?php else : ?>
  <div class="shl-card" style="padding:0">
    <table class="shl-table">
      <thead>
        <tr>
          <th>Prénom</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Adhérent</th>
          <th>Réalisées</th>
          <th>À venir</th>
          <th>Première date</th>
          <th>Dernière date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $volunteers as $v ) : ?>
        <tr>
          <td><?php echo esc_html( $v->firstname ); ?></td>
          <td><?php echo esc_html( $v->lastname ); ?></td>
          <td><a href="mailto:<?php echo esc_attr( $v->email ); ?>"><?php echo esc_html( $v->email ); ?></a></td>
          <td><?php echo $v->phone ? esc_html( $v->phone ) : '—'; ?></td>
          <td><?php echo $v->is_member ? '✅' : '—'; ?></td>
          <td><strong><?php echo esc_html( $v->nb_done ); ?></strong></td>
          <td><?php echo esc_html( $v->nb_upcoming ); ?></td>
          <td><?php echo $v->first_date ? esc_html( date_i18n( 'd/m/Y', strtotime( $v->first_date ) ) ) : '—'; ?></td>
          <td><?php echo $v->last_date  ? esc_html( date_i18n( 'd/m/Y', strtotime( $v->last_date ) ) )  : '—'; ?></td>
          <td class="shl-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-volunteers&email=' . rawurlencode( $v->email ) ) ); ?>">Historique</a>
            |
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&search=' . rawurlencode( $v->email ) ) ); ?>">Inscriptions</a>
            |
            <a href="<?php echo esc_url( SHL_Tortues_Attestation::get_url( $v->email, intval( gmdate( 'Y' ) ) ) ); ?>" target="_blank">Attestation <?php echo esc_html( gmdate( 'Y' ) ); ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php endif; ?>

</div>
