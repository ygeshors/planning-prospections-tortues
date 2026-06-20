<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables : $year (int), $season (array), $extended (array)

$color      = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$gen_date   = date_i18n( 'd F Y', strtotime( gmdate( 'Y-m-d' ) ) );
$years_avail = range( intval( gmdate( 'Y' ) ), 2024 );

$obs_labels_map = array(
	'none'      => 'Aucune trace',
	'suspect'   => 'Trace suspecte',
	'confirmed' => 'Trace confirmée',
	'other'     => 'Autre observation',
);

$top_volunteers = $extended['top_volunteers'] ?? array();
$total_km       = round( $extended['total_km'] ?? 0, 1 );
$total_hours    = round( $extended['total_hours'] ?? 0, 1 );
$nb_tracks      = $extended['nb_tracks'] ?? 0;

// Max valeur pour les barres CSS
$max_obs = 1;
foreach ( $season['obs_by_type'] as $o ) {
	if ( (int) $o->cnt > $max_obs ) $max_obs = (int) $o->cnt;
}
$max_zone = 1;
foreach ( $season['by_zone'] as $z ) {
	if ( (int) $z->slots > $max_zone ) $max_zone = (int) $z->slots;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rapport de saison <?php echo esc_html( $year ); ?> – Sauvegarde Hérault Littoral</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222; background: #f4f6f8; }
  .rapport-wrap { max-width: 900px; margin: 0 auto; padding: 24px 16px; }

  .rapport-controls { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; print-color-adjust: economy; }
  @media print { .rapport-controls { display: none; } body { background: white; } .rapport-wrap { padding: 0; } }

  .r-header { background: <?php echo $color; ?>; color: #fff; border-radius: 12px; padding: 28px 32px; margin-bottom: 20px; display: flex; align-items: center; gap: 24px; }
  .r-header img { height: 64px; width: auto; background: rgba(255,255,255,.15); border-radius: 8px; padding: 6px; }
  .r-header h1 { font-size: 22px; margin-bottom: 4px; }
  .r-header p { font-size: 13px; opacity: .8; margin-top: 2px; }

  .r-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
  .r-stat { background: #fff; border: 1px solid #e0e8f0; border-radius: 10px; padding: 16px; text-align: center; }
  .r-stat .icon { font-size: 26px; margin-bottom: 6px; }
  .r-stat .val { font-size: 24px; font-weight: 800; color: <?php echo $color; ?>; }
  .r-stat .lbl { font-size: 11px; color: #888; margin-top: 4px; }

  .r-card { background: #fff; border: 1px solid #e0e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 16px; }
  .r-card h2 { font-size: 16px; font-weight: 700; color: #333; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #f0f5f9; }

  .r-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .r-table th { text-align: left; padding: 8px 12px; background: #f0f5f9; font-weight: 700; color: #555; border: 1px solid #e0e8f0; }
  .r-table td { padding: 8px 12px; border: 1px solid #e0e8f0; }
  .r-table tr:nth-child(even) td { background: #f8fbff; }

  .r-bar-wrap { margin-bottom: 8px; }
  .r-bar-label { font-size: 12px; color: #555; margin-bottom: 3px; display: flex; justify-content: space-between; }
  .r-bar-track { background: #e8f0f8; border-radius: 4px; height: 14px; overflow: hidden; }
  .r-bar-fill { height: 100%; border-radius: 4px; background: <?php echo $color; ?>; transition: width .3s; }
  .r-badge { display: inline-block; background: #e3f2fd; color: <?php echo $color; ?>; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
  .medal { font-size: 16px; }
  .r-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 600px) { .r-two-col { grid-template-columns: 1fr; } }

  .r-footer { text-align: center; font-size: 11px; color: #bbb; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e8f0; }
  .r-print-btn { background: <?php echo $color; ?>; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
  .r-print-btn:hover { opacity: .9; }
</style>
</head>
<body>
<div class="rapport-wrap">

  <!-- Contrôles (masqués à l'impression) -->
  <div class="rapport-controls">
    <button class="r-print-btn" onclick="window.print()">🖨️ Imprimer / Exporter PDF</button>
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="page" value="shl-tortues-rapport">
      <select name="year" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px">
        <?php foreach ( $years_avail as $y ) : ?>
          <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>><?php echo esc_html( $y ); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues' ) ); ?>"
       style="color:#666;text-decoration:none;font-size:13px">← Tableau de bord</a>
  </div>

  <!-- En-tête -->
  <div class="r-header">
    <img src="https://ashl.fr/wp-content/uploads/2025/03/Copie-de-Copie-de-Sans-titre-297-x-210-mm-6-300x212.png"
         alt="SHL" onerror="this.outerHTML='<span style=\'font-size:48px\'>🐢</span>'">
    <div>
      <h1>Bilan de la saison <?php echo esc_html( $year ); ?></h1>
      <p>Prospections Tortues Marines · Association Sauvegarde Hérault Littoral</p>
      <p>Généré le <?php echo esc_html( $gen_date ); ?></p>
    </div>
  </div>

  <!-- Stats principales -->
  <div class="r-stats-grid">
    <?php
    $stats_cards = array(
      array( '🏁', $season['done'] . ' / ' . $season['total_slots'], 'Créneaux réalisés / planifiés' ),
      array( '👥', $season['unique_volunteers'],                       'Bénévoles uniques' ),
      array( '🙋', $season['total_participations'],                    'Participations totales' ),
      array( '⏱️', $total_hours . 'h',                                'Heures bénévoles' ),
      array( '📏', $total_km . ' km',                                  'Distance parcourue (GPS)' ),
      array( '📸', $season['total_obs'],                               'Observations terrain' ),
      array( '🗺️', $nb_tracks,                                         'Tracés GPS enregistrés' ),
      array( '🏖️', count( $season['by_zone'] ),                        'Zones couvertes' ),
    );
    foreach ( $stats_cards as $c ) : ?>
    <div class="r-stat">
      <div class="icon"><?php echo $c[0]; // phpcs:ignore ?></div>
      <div class="val"><?php echo esc_html( $c[1] ); ?></div>
      <div class="lbl"><?php echo esc_html( $c[2] ); ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="r-two-col">

    <!-- Zones -->
    <div class="r-card">
      <h2>🏖️ Activité par zone</h2>
      <?php if ( empty( $season['by_zone'] ) ) : ?>
        <p style="color:#bbb;font-size:13px">Aucune donnée.</p>
      <?php else : ?>
        <?php foreach ( $season['by_zone'] as $z ) :
          $pct = $max_zone > 0 ? round( $z->slots / $max_zone * 100 ) : 0;
        ?>
        <div class="r-bar-wrap">
          <div class="r-bar-label">
            <span><?php echo esc_html( $z->zone_name ); ?></span>
            <span style="color:#888"><?php echo esc_html( $z->slots ); ?> créneaux · <?php echo esc_html( $z->participations ); ?> part.</span>
          </div>
          <div class="r-bar-track"><div class="r-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Observations -->
    <div class="r-card">
      <h2>📸 Observations par type</h2>
      <?php if ( empty( $season['obs_by_type'] ) ) : ?>
        <p style="color:#bbb;font-size:13px">Aucune observation enregistrée.</p>
      <?php else : ?>
        <?php
        $obs_colors = array( 'none' => '#4caf7d', 'suspect' => '#e8a23a', 'confirmed' => '#e05555', 'other' => '#9b7fd4' );
        foreach ( $season['obs_by_type'] as $o ) :
          $pct = $max_obs > 0 ? round( $o->cnt / $max_obs * 100 ) : 0;
          $lbl = $obs_labels_map[ $o->obs_type ] ?? $o->obs_type;
          $col = $obs_colors[ $o->obs_type ] ?? $color;
        ?>
        <div class="r-bar-wrap">
          <div class="r-bar-label">
            <span><?php echo esc_html( $lbl ); ?></span>
            <span style="color:#888"><?php echo esc_html( $o->cnt ); ?></span>
          </div>
          <div class="r-bar-track"><div class="r-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;background:<?php echo esc_attr( $col ); ?>"></div></div>
        </div>
        <?php endforeach; ?>
        <p style="font-size:12px;color:#888;margin-top:10px">Total : <?php echo esc_html( $season['total_obs'] ); ?> observation(s)</p>
      <?php endif; ?>
    </div>

  </div>

  <!-- Activité mensuelle -->
  <?php if ( ! empty( $season['weekly'] ) ) : ?>
  <div class="r-card">
    <h2>📅 Activité par semaine</h2>
    <div style="overflow-x:auto">
      <table class="r-table">
        <thead>
          <tr>
            <th>Semaine</th>
            <th style="text-align:center">Participations</th>
            <th style="width:60%">Activité</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $max_week = 1;
          foreach ( $season['weekly'] as $w ) {
            if ( (int) $w->cnt > $max_week ) $max_week = (int) $w->cnt;
          }
          foreach ( $season['weekly'] as $w ) :
            $pct  = $max_week > 0 ? round( $w->cnt / $max_week * 100 ) : 0;
            $wlbl = $w->week_start ? 'Sem. ' . date_i18n( 'W', strtotime( $w->week_start ) ) . ' (' . date_i18n( 'd/m', strtotime( $w->week_start ) ) . ')' : 'Sem. ' . $w->wk;
          ?>
          <tr>
            <td><?php echo esc_html( $wlbl ); ?></td>
            <td style="text-align:center;font-weight:700"><?php echo esc_html( $w->cnt ); ?></td>
            <td><div style="background:#e8f0f8;border-radius:4px;height:12px;overflow:hidden"><div style="width:<?php echo esc_attr( $pct ); ?>%;height:100%;background:<?php echo $color; ?>;border-radius:4px"></div></div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Top bénévoles -->
  <?php if ( ! empty( $top_volunteers ) ) : ?>
  <div class="r-card">
    <h2>🌟 Bénévoles les plus actifs</h2>
    <table class="r-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Bénévole</th>
          <th style="text-align:center">Participations</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $medals = array( '🥇', '🥈', '🥉' );
        foreach ( $top_volunteers as $i => $v ) :
          $medal = $medals[ $i ] ?? '';
        ?>
        <tr>
          <td><?php echo $medal ? '<span class="medal">' . $medal . '</span>' : esc_html( $i + 1 ); // phpcs:ignore ?></td>
          <td>
            <?php echo esc_html( $v->firstname . ' ' . mb_strtoupper( mb_substr( $v->lastname, 0, 1 ) ) . '.' ); ?>
          </td>
          <td style="text-align:center;font-weight:700;color:<?php echo $color; ?>"><?php echo esc_html( $v->nb_done ); ?></td>
          <td><?php echo $v->is_member ? '<span class="r-badge">Adhérent·e</span>' : '<span style="font-size:12px;color:#aaa">Extérieur·e</span>'; // phpcs:ignore ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="r-footer">
    Rapport généré automatiquement par le plugin Planning Prospections Tortues Marines v<?php echo esc_html( SHL_TORTUES_VERSION ); ?><br>
    Association Sauvegarde Hérault Littoral · Hérault, France
  </div>

</div>
</body>
</html>
