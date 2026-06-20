<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">🐢</span>
    <div>
      <h1>Planning Prospections Tortues Marines</h1>
      <p class="shl-subtitle">Tableau de bord – Association Sauvegarde Hérault Littoral</p>
    </div>
  </div>

  <!-- Statistiques -->
  <div class="shl-stats-grid">
    <div class="shl-stat-card shl-stat-blue">
      <div class="shl-stat-icon">📅</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats['upcoming'] ); ?></div>
      <div class="shl-stat-label">Créneaux à venir</div>
    </div>
    <div class="shl-stat-card shl-stat-green">
      <div class="shl-stat-icon">👤</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats['registrations'] ); ?></div>
      <div class="shl-stat-label">Inscriptions totales</div>
    </div>
    <div class="shl-stat-card shl-stat-orange">
      <div class="shl-stat-icon">✅</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats['full'] ); ?></div>
      <div class="shl-stat-label">Créneaux complets</div>
    </div>
    <div class="shl-stat-card shl-stat-teal">
      <div class="shl-stat-icon">🚁</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats['drone'] ); ?></div>
      <div class="shl-stat-label">Créneaux drone à venir</div>
    </div>
    <div class="shl-stat-card shl-stat-sand">
      <div class="shl-stat-icon">🏁</div>
      <div class="shl-stat-value"><?php echo esc_html( $stats['done'] ); ?></div>
      <div class="shl-stat-label">Prospections réalisées</div>
    </div>
  </div>

  <!-- Actions rapides -->
  <div class="shl-quick-actions">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=new' ) ); ?>" class="shl-btn shl-btn-primary">+ Nouveau créneau</a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-zones&action=new' ) ); ?>" class="shl-btn shl-btn-secondary">+ Nouvelle zone</a>
    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shl-tortues-registrations&action=export_csv' ), 'shl_export_csv' ) ); ?>" class="shl-btn shl-btn-outline">⬇ Export CSV</a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-rapport' ) ); ?>" class="shl-btn shl-btn-outline">📊 Rapport saison</a>
  </div>

  <?php if ( ! empty( $weather_alerts ) ) : ?>
  <!-- Alertes météo -->
  <div class="shl-card" style="border-left:4px solid #ffc107;background:linear-gradient(to right,#fffdf0,#fff)">
    <div class="shl-card-header" style="border-bottom:1px solid #ffeeba;padding-bottom:12px;margin-bottom:12px">
      <h2 style="color:#856404;display:flex;align-items:center;gap:8px">
        ⚠️ Alertes météo
        <span style="background:#ffc107;color:#333;font-size:12px;font-weight:600;padding:2px 10px;border-radius:20px"><?php echo esc_html( count( $weather_alerts ) ); ?></span>
      </h2>
      <p style="font-size:12px;color:#856404;margin:0">Conditions potentiellement dangereuses détectées pour des créneaux à venir</p>
    </div>
    <?php foreach ( $weather_alerts as $alert ) :
      $wx_slot    = $alert['slot'];
      $wx_fc      = $alert['forecast'];
      $wx_danger  = $alert['danger'];
      $wx_date    = date_i18n( 'd/m/Y', strtotime( $wx_slot->date ) );
      $wx_reason  = $wx_danger['reason'];
    ?>
    <div class="shl-wx-alert-row" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:12px 0;border-bottom:1px solid #ffeeba">
      <div>
        <div style="font-weight:700;font-size:14px;color:#333">
          <?php echo esc_html( $wx_date ); ?> — <?php echo esc_html( $wx_slot->zone_name ); ?>, <?php echo esc_html( $wx_slot->commune ); ?>
          <span style="font-weight:400;color:#888;font-size:12px;margin-left:6px">⏰ <?php echo esc_html( substr( $wx_slot->time_start, 0, 5 ) ); ?> · <?php echo esc_html( $wx_slot->places_taken ); ?> inscrit(s)</span>
        </div>
        <div style="font-size:13px;color:#856404;margin-top:4px">
          <?php echo esc_html( $wx_danger['icon'] ); ?> <?php echo esc_html( $wx_danger['reason'] ); ?>
          <span style="color:#aaa;margin:0 4px">·</span>
          <?php echo esc_html( $wx_fc['icon'] ); ?> <?php echo esc_html( $wx_fc['label'] ); ?> — Vent : <?php echo esc_html( $wx_fc['wind'] ); ?> km/h · Précip. : <?php echo esc_html( $wx_fc['rain'] ); ?> mm
        </div>
      </div>
      <button class="shl-btn shl-wx-cancel-btn"
              style="background:#e05555;color:#fff;border:none;cursor:pointer"
              data-slot="<?php echo esc_attr( $wx_slot->id ); ?>"
              data-date="<?php echo esc_attr( $wx_date ); ?>"
              data-zone="<?php echo esc_attr( $wx_slot->zone_name ); ?>"
              data-count="<?php echo esc_attr( $wx_slot->places_taken ); ?>"
              data-reason="<?php echo esc_attr( $wx_reason ); ?>">
        🚫 Annuler ce créneau
      </button>
    </div>
    <?php endforeach; ?>
    <p style="font-size:12px;color:#aaa;margin:10px 0 0">
      Les alertes météo sont vérifiées automatiquement chaque matin.
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-settings' ) ); ?>" style="color:#2E86AB">Configurer les seuils →</a>
    </p>
  </div>
  <?php endif; ?>

  <!-- Prochains créneaux -->
  <div class="shl-card">
    <div class="shl-card-header">
      <h2>Prochains créneaux</h2>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots' ) ); ?>" class="shl-link">Voir tous →</a>
    </div>
    <?php if ( empty( $upcoming ) ) : ?>
      <p class="shl-empty">Aucun créneau à venir. <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=new' ) ); ?>">Créer un créneau</a></p>
    <?php else : ?>
      <table class="shl-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Météo</th>
            <th>Heure</th>
            <th>Plage</th>
            <th>Commune</th>
            <th>Type</th>
            <th>Places</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $upcoming as $slot ) :
            $type_labels   = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );
            $status_labels = array( 'open' => 'Ouvert', 'full' => 'Complet', 'cancelled' => 'Annulé', 'done' => 'Réalisé' );
            $status_class  = array( 'open' => 'shl-badge-green', 'full' => 'shl-badge-orange', 'cancelled' => 'shl-badge-red', 'done' => 'shl-badge-grey' );
            $wx = null;
            if ( ! empty( $slot->latitude ) && ! empty( $slot->longitude ) ) {
                $wx = SHL_Tortues_Weather::get_forecast( $slot->latitude, $slot->longitude, $slot->date );
            }
          ?>
          <tr>
            <td><strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ); ?></strong></td>
            <td>
              <?php if ( $wx ) : ?>
                <span title="<?php echo esc_attr( $wx['label'] . ' · ' . $wx['tmax'] . '°/' . $wx['tmin'] . '° · Vent ' . $wx['wind'] . ' km/h' ); ?>"
                      style="cursor:default;white-space:nowrap;font-size:16px">
                  <?php echo $wx['icon']; // phpcs:ignore ?>
                  <span style="font-size:12px;color:#666"><?php echo esc_html( $wx['tmax'] . '°' ); ?></span>
                </span>
              <?php else : ?>
                <span style="color:#ccc;font-size:11px">—</span>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html( substr( $slot->time_start, 0, 5 ) ); ?></td>
            <td><?php echo esc_html( $slot->zone_name ); ?></td>
            <td><?php echo esc_html( $slot->commune ); ?></td>
            <td><?php echo esc_html( $type_labels[ $slot->type_prospect ] ?? $slot->type_prospect ); ?></td>
            <td><?php echo esc_html( $slot->places_taken ); ?>/<?php echo esc_html( $slot->places_total ); ?></td>
            <td><span class="shl-badge <?php echo esc_attr( $status_class[ $slot->status ] ?? 'shl-badge-grey' ); ?>"><?php echo esc_html( $status_labels[ $slot->status ] ?? $slot->status ); ?></span></td>
            <td>
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=edit&id=' . $slot->id ) ); ?>">Modifier</a> |
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot->id ) ); ?>">Inscrits (<?php echo esc_html( $slot->places_taken ); ?>)</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- ── Bilan de saison ─────────────────────────────────────────────────── -->
  <?php
  $obs_labels_map = array(
      'none'      => 'Aucune trace',
      'suspect'   => 'Trace suspecte',
      'confirmed' => 'Trace confirmée',
      'other'     => 'Autre observation',
  );
  $obs_colors = array( 'none' => '#64b5c6', 'suspect' => '#e8a23a', 'confirmed' => '#4caf7d', 'other' => '#9b7fd4' );

  $zone_labels     = array();
  $zone_slots_data = array();
  $zone_part_data  = array();
  foreach ( $season['by_zone'] as $z ) {
      $zone_labels[]     = $z->zone_name;
      $zone_slots_data[] = (int) $z->slots;
      $zone_part_data[]  = (int) $z->participations;
  }

  $week_labels = array();
  $week_data   = array();
  foreach ( $season['weekly'] as $w ) {
      $week_labels[] = date_i18n( 'd/m', strtotime( $w->week_start ) );
      $week_data[]   = (int) $w->cnt;
  }

  $obs_chart_labels = array();
  $obs_chart_data   = array();
  $obs_chart_colors = array();
  foreach ( $season['obs_by_type'] as $o ) {
      $obs_chart_labels[] = $obs_labels_map[ $o->obs_type ] ?? $o->obs_type;
      $obs_chart_data[]   = (int) $o->cnt;
      $obs_chart_colors[] = $obs_colors[ $o->obs_type ] ?? '#aaa';
  }
  ?>
  <div class="shl-card" style="margin-top:24px">
    <div class="shl-card-header">
      <h2>📊 Bilan de saison</h2>
      <form method="get" style="display:flex;align-items:center;gap:8px;margin:0">
        <input type="hidden" name="page" value="shl-tortues">
        <label style="font-size:13px;color:#666;margin:0">Saison&nbsp;:</label>
        <select name="season_year" onchange="this.form.submit()" style="padding:4px 8px;border:1px solid #ddd;border-radius:4px;font-size:13px">
          <?php for ( $y = 2024; $y <= intval( gmdate( 'Y' ) ) + 1; $y++ ) : ?>
            <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>><?php echo esc_html( $y ); ?></option>
          <?php endfor; ?>
        </select>
      </form>
    </div>

    <div class="shl-stats-grid" style="margin:0 0 24px">
      <div class="shl-stat-card shl-stat-blue">
        <div class="shl-stat-icon">🏁</div>
        <div class="shl-stat-value"><?php echo esc_html( $season['done'] ); ?> <span style="font-size:14px;font-weight:400">/ <?php echo esc_html( $season['total_slots'] ); ?></span></div>
        <div class="shl-stat-label">Créneaux réalisés</div>
      </div>
      <div class="shl-stat-card shl-stat-green">
        <div class="shl-stat-icon">🧑‍🤝‍🧑</div>
        <div class="shl-stat-value"><?php echo esc_html( $season['unique_volunteers'] ); ?></div>
        <div class="shl-stat-label">Bénévoles uniques</div>
      </div>
      <div class="shl-stat-card shl-stat-teal">
        <div class="shl-stat-icon">👥</div>
        <div class="shl-stat-value"><?php echo esc_html( $season['total_participations'] ); ?></div>
        <div class="shl-stat-label">Participations totales</div>
      </div>
      <div class="shl-stat-card shl-stat-orange">
        <div class="shl-stat-icon">⏱️</div>
        <div class="shl-stat-value">~<?php echo esc_html( $season['done'] * 2 ); ?>h</div>
        <div class="shl-stat-label">Heures bénévoles estimées</div>
      </div>
      <div class="shl-stat-card shl-stat-sand">
        <div class="shl-stat-icon">📸</div>
        <div class="shl-stat-value"><?php echo esc_html( $season['total_obs'] ); ?></div>
        <div class="shl-stat-label">Observations terrain</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">

      <?php if ( ! empty( $zone_labels ) ) : ?>
      <div>
        <h3 style="font-size:14px;color:#444;margin:0 0 12px">Participation par plage</h3>
        <canvas id="shl-chart-zone" style="max-height:220px"></canvas>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $week_labels ) ) : ?>
      <div>
        <h3 style="font-size:14px;color:#444;margin:0 0 12px">Activité par semaine (participations)</h3>
        <canvas id="shl-chart-week" style="max-height:220px"></canvas>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $obs_chart_labels ) ) : ?>
      <div>
        <h3 style="font-size:14px;color:#444;margin:0 0 12px">Observations par type</h3>
        <canvas id="shl-chart-obs" style="max-height:220px"></canvas>
      </div>
      <?php endif; ?>

    </div>

    <?php if ( empty( $zone_labels ) && empty( $week_labels ) ) : ?>
      <p style="color:#888;font-size:13px;text-align:center;padding:20px 0">Aucune donnée pour la saison <?php echo esc_html( $year ); ?>.</p>
    <?php endif; ?>
  </div>

  <script>
  (function() {
    var shlChartDefaults = {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } }
    };

    <?php if ( ! empty( $zone_labels ) ) : ?>
    new Chart( document.getElementById('shl-chart-zone'), {
      type: 'bar',
      data: {
        labels: <?php echo wp_json_encode( $zone_labels ); ?>,
        datasets: [
          { label: 'Créneaux', data: <?php echo wp_json_encode( $zone_slots_data ); ?>, backgroundColor: '#2E86AB99', borderColor: '#2E86AB', borderWidth:1 },
          { label: 'Participations', data: <?php echo wp_json_encode( $zone_part_data ); ?>, backgroundColor: '#4caf7d99', borderColor: '#4caf7d', borderWidth:1 }
        ]
      },
      options: Object.assign( {}, shlChartDefaults, { plugins: { legend: { display: true, position: 'bottom' } } } )
    });
    <?php endif; ?>

    <?php if ( ! empty( $week_labels ) ) : ?>
    new Chart( document.getElementById('shl-chart-week'), {
      type: 'line',
      data: {
        labels: <?php echo wp_json_encode( $week_labels ); ?>,
        datasets: [{ label: 'Participations', data: <?php echo wp_json_encode( $week_data ); ?>,
          borderColor: '#2E86AB', backgroundColor: '#2E86AB22', fill: true, tension: 0.3, pointRadius: 3 }]
      },
      options: shlChartDefaults
    });
    <?php endif; ?>

    <?php if ( ! empty( $obs_chart_labels ) ) : ?>
    new Chart( document.getElementById('shl-chart-obs'), {
      type: 'doughnut',
      data: {
        labels: <?php echo wp_json_encode( $obs_chart_labels ); ?>,
        datasets: [{ data: <?php echo wp_json_encode( $obs_chart_data ); ?>,
          backgroundColor: <?php echo wp_json_encode( $obs_chart_colors ); ?> }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    <?php endif; ?>
  })();
  </script>

  <!-- Shortcode info -->
  <div class="shl-card shl-card-info">
    <h3>📋 Intégration sur votre site</h3>
    <p>Utilisez le shortcode suivant pour afficher le calendrier public sur n'importe quelle page WordPress :</p>
    <code class="shl-shortcode">[planning_tortues]</code>
  </div>

</div>

<script>
jQuery(function($) {
  $(document).on('click', '.shl-wx-cancel-btn', function() {
    var $btn   = $(this);
    var slotId = $btn.data('slot');
    var date   = $btn.data('date');
    var zone   = $btn.data('zone');
    var count  = parseInt($btn.data('count'), 10);
    var reason = $btn.data('reason');

    var msg = 'Annuler le créneau du ' + date + ' (' + zone + ') ?\n\n';
    msg += count > 0 ? count + ' bénévole(s) seront notifié(s) par email.' : 'Aucun bénévole inscrit.';

    if (!confirm(msg)) return;

    $btn.prop('disabled', true).text('Annulation…');

    $.post(shlAdmin.ajax, {
      action : 'shl_weather_cancel_slot',
      nonce  : shlAdmin.nonce,
      slot_id: slotId,
      reason : reason
    }, function(res) {
      if (res.success) {
        var $row = $btn.closest('.shl-wx-alert-row');
        $row.fadeOut(300, function() {
          $row.remove();
          if ($('.shl-wx-alert-row').length === 0) {
            $btn.closest('.shl-card').fadeOut(200);
          }
        });
      } else {
        alert('Erreur : ' + (res.data || 'Impossible d\'annuler ce créneau.'));
        $btn.prop('disabled', false).text('🚫 Annuler ce créneau');
      }
    });
  });
});
</script>
