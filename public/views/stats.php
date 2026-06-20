<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables : $year (int), $stats (array from get_season_stats)
$chart_id = 'shl-stats-obs-' . wp_rand( 1000, 9999 );

$obs_labels_map = array(
	'none'      => 'Aucune trace',
	'suspect'   => 'Trace suspecte',
	'confirmed' => 'Trace confirmée',
	'other'     => 'Autre observation',
);
$obs_colors = array(
	'none'      => '#64b5c6',
	'suspect'   => '#e8a23a',
	'confirmed' => '#4caf7d',
	'other'     => '#9b7fd4',
);

$obs_chart_labels = array();
$obs_chart_data   = array();
$obs_chart_colors = array();
foreach ( $stats['obs_by_type'] as $o ) {
	$obs_chart_labels[] = $obs_labels_map[ $o->obs_type ] ?? $o->obs_type;
	$obs_chart_data[]   = (int) $o->cnt;
	$obs_chart_colors[] = $obs_colors[ $o->obs_type ] ?? '#aaa';
}
?>
<div class="shl-public-stats" style="font-family:system-ui,sans-serif;max-width:780px;margin:0 auto">

  <div style="text-align:center;margin-bottom:24px">
    <span style="font-size:40px">🐢</span>
    <h2 style="margin:8px 0 4px;font-size:22px;color:#1a5f7a">Bilan des prospections <?php echo esc_html( $year ); ?></h2>
    <p style="color:#666;font-size:14px;margin:0">Association Sauvegarde Hérault Littoral</p>
  </div>

  <!-- Cartes stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:28px">

    <?php
    $cards = array(
      array( '🏁', $stats['done'],                        'Prospections<br>réalisées',     '#1a5f7a' ),
      array( '🧑‍🤝‍🧑', $stats['unique_volunteers'],         'Bénévoles<br>mobilisés',        '#4caf7d' ),
      array( '⏱️', '~' . ( $stats['done'] * 2 ) . 'h',  'Heures<br>bénévoles',           '#e8a23a' ),
      array( '📸', $stats['total_obs'],                   'Observations<br>terrain',        '#9b7fd4' ),
    );
    foreach ( $cards as $c ) :
    ?>
    <div style="background:#fff;border:1px solid #e0eaf0;border-radius:12px;padding:18px 12px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.05)">
      <div style="font-size:28px;margin-bottom:6px"><?php echo $c[0]; // phpcs:ignore ?></div>
      <div style="font-size:26px;font-weight:800;color:<?php echo esc_attr( $c[3] ); ?>"><?php echo esc_html( $c[1] ); ?></div>
      <div style="font-size:12px;color:#888;margin-top:4px;line-height:1.4"><?php echo $c[2]; // phpcs:ignore ?></div>
    </div>
    <?php endforeach; ?>

  </div>

  <?php if ( ! empty( $obs_chart_labels ) ) : ?>
  <!-- Graphique observations -->
  <div style="background:#fff;border:1px solid #e0eaf0;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:20px">
    <h3 style="margin:0 0 16px;font-size:15px;color:#444;text-align:center">Répartition des observations</h3>
    <div style="max-width:280px;margin:0 auto">
      <canvas id="<?php echo esc_attr( $chart_id ); ?>"></canvas>
    </div>
  </div>
  <script>
  (function() {
    var ctx = document.getElementById(<?php echo wp_json_encode( $chart_id ); ?>);
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: <?php echo wp_json_encode( $obs_chart_labels ); ?>,
        datasets: [{ data: <?php echo wp_json_encode( $obs_chart_data ); ?>, backgroundColor: <?php echo wp_json_encode( $obs_chart_colors ); ?>, borderWidth: 2, borderColor: '#fff' }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 13 }, padding: 16 } }
        }
      }
    });
  })();
  </script>
  <?php endif; ?>

  <?php if ( 0 === (int) $stats['done'] ) : ?>
    <p style="text-align:center;color:#aaa;font-size:14px;padding:20px 0">Aucune donnée pour la saison <?php echo esc_html( $year ); ?>.</p>
  <?php else : ?>
    <p style="text-align:center;font-size:13px;color:#888;margin-top:4px">
      Données collectées par les bénévoles sur les plages de l'Hérault dans le cadre du programme RTMMF.
    </p>
  <?php endif; ?>

</div>
