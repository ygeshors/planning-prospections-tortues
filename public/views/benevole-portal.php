<?php if ( ! defined( 'ABSPATH' ) ) exit;
// Variables : $email, $session, $first_reg, $upcoming, $past, $att_url, $att_url_prev, $logout_url, $year, $tracks, $badges
$color       = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$prenom      = $first_reg ? esc_html( $first_reg->firstname ) : 'Bénévole';
$nom_init    = $first_reg ? strtoupper( mb_substr( $first_reg->lastname, 0, 1 ) ) . '.' : '';
$type_labels = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte' );

// Stats
$today       = gmdate( 'Y-m-d' );
$nb_done     = count( $past );
$nb_upcoming = count( $upcoming );
$nb_obs      = 0;
$heures      = 0.0;
$total_km    = 0.0;
foreach ( $past as $h ) {
	$nb_obs += intval( $h->obs_count );
	if ( ! empty( $h->actual_time_start ) && ! empty( $h->actual_time_end ) ) {
		$ts = strtotime( '2000-01-01 ' . $h->actual_time_start );
		$te = strtotime( '2000-01-01 ' . $h->actual_time_end );
		$heures += ( $te > $ts ) ? ( $te - $ts ) / 3600.0 : 2.0;
	} else {
		$heures += 2.0;
	}
}
foreach ( $tracks as $t ) {
	$total_km += (float) $t->distance_m / 1000;
}
$heures   = round( $heures, 1 );
$total_km = round( $total_km, 1 );

// Progression vers prochain badge
$earned_count = count( $badges['earned'] ?? array() );
$locked_count = count( $badges['locked'] ?? array() );
$total_badges = $earned_count + $locked_count;
$badge_pct    = $total_badges > 0 ? round( $earned_count / $total_badges * 100 ) : 0;

// Prochain badge à débloquer
$next_badge = ! empty( $badges['locked'] ) ? array_values( $badges['locked'] )[0] : null;
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<div class="shl-bp-wrap" style="max-width:700px;margin:0 auto;font-family:'Nunito',system-ui,sans-serif;color:#1e3040">

  <!-- ══ PASSEPORT NATURALISTE ══ -->
  <div style="background:linear-gradient(135deg,<?php echo $color; ?> 0%,#1a5f7a 60%,#0d3d52 100%);border-radius:18px;padding:0;margin-bottom:20px;overflow:hidden;box-shadow:0 8px 32px rgba(46,134,171,.3);position:relative">

    <!-- Motif décoratif -->
    <div style="position:absolute;inset:0;background:url('data:image/svg+xml,%3Csvg width=\'80\' height=\'80\' viewBox=\'0 0 80 80\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\'%3E%3Cpath fill=\'%23ffffff\' fill-opacity=\'0.03\' d=\'M40 0C17.9 0 0 17.9 0 40s17.9 40 40 40 40-17.9 40-40S62.1 0 40 0zm0 60c-11 0-20-9-20-20s9-20 20-20 20 9 20 20-9 20-20 20z\'/%3E%3C/g%3E%3C/svg%3E') repeat;pointer-events:none"></div>

    <!-- Header passeport -->
    <div style="padding:20px 24px 14px;position:relative">
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <!-- Avatar initiale -->
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#fff;flex-shrink:0;backdrop-filter:blur(4px)">
          <?php echo esc_html( mb_strtoupper( mb_substr( $prenom, 0, 1 ) ) ); ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:20px;font-weight:900;color:#fff;line-height:1.2">
            Bonjour <?php echo $prenom; ?> <?php echo esc_html( $nom_init ); ?> 👋
          </div>
          <div style="font-size:12px;color:rgba(255,255,255,.7);margin-top:2px;font-weight:600"><?php echo esc_html( $email ); ?></div>
          <div style="margin-top:6px">
            <span style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;backdrop-filter:blur(4px)">
              🐢 Naturaliste bénévole · Saison <?php echo esc_html( $year ); ?>
            </span>
          </div>
        </div>
        <a href="<?php echo esc_url( $logout_url ); ?>"
           style="font-size:12px;color:rgba(255,255,255,.7);text-decoration:none;background:rgba(255,255,255,.12);padding:7px 14px;border-radius:20px;white-space:nowrap;font-weight:700;border:1px solid rgba(255,255,255,.2);flex-shrink:0">
          Déconnexion →
        </a>
      </div>
    </div>

    <!-- Barre de progression badges -->
    <?php if ( $total_badges > 0 ) : ?>
    <div style="padding:10px 24px 16px;border-top:1px solid rgba(255,255,255,.1)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:11px;color:rgba(255,255,255,.8);font-weight:700;text-transform:uppercase;letter-spacing:.5px">
          🏅 Progression badges
        </span>
        <span style="font-size:12px;color:#fff;font-weight:800"><?php echo esc_html( $earned_count ); ?>/<?php echo esc_html( $total_badges ); ?></span>
      </div>
      <div style="background:rgba(0,0,0,.2);border-radius:20px;height:8px;overflow:hidden">
        <div style="height:100%;width:<?php echo esc_attr( $badge_pct ); ?>%;background:linear-gradient(90deg,#f0c040,#ffd700);border-radius:20px;transition:width .6s ease;box-shadow:0 0 8px rgba(240,192,64,.6)"></div>
      </div>
      <?php if ( $next_badge ) : ?>
      <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:5px;font-weight:600">
        Prochain : <?php echo $next_badge['icon']; ?> <?php echo esc_html( $next_badge['name'] ); ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ STATS RAPIDES ══ -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:20px">
    <?php
    $stat_cards = array(
      array( '🏁', $nb_done,   'Prospections<br>réalisées',  $color ),
      array( '📅', $nb_upcoming,'À venir',                    '#4caf7d' ),
      array( '⏱️', $heures > 0 ? $heures . ' h' : '—',      'Heures<br>bénévoles',  '#e8a23a' ),
      array( '🗺️', $total_km > 0 ? $total_km . ' km' : '—', 'Parcourus<br>(GPS)',    '#9b7fd4' ),
    );
    foreach ( $stat_cards as $c ) :
    ?>
    <div style="background:#fff;border:1px solid #dce9f0;border-radius:14px;padding:14px 12px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.05);transition:transform .15s,box-shadow .15s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 18px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,.05)'">
      <div style="font-size:22px;margin-bottom:5px"><?php echo $c[0]; // phpcs:ignore ?></div>
      <div style="font-size:22px;font-weight:900;color:<?php echo esc_attr( $c[3] ); ?>"><?php echo esc_html( $c[1] ); ?></div>
      <div style="font-size:10.5px;color:#888;margin-top:3px;line-height:1.4;font-weight:700;text-transform:uppercase;letter-spacing:.3px"><?php echo $c[2]; // phpcs:ignore ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ══ ATTESTATION ══ -->
  <div style="background:linear-gradient(135deg,#e8f5e9,#d4f0e0);border:2px solid #4caf7d;border-radius:14px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;box-shadow:0 2px 10px rgba(76,175,125,.15)">
    <div>
      <div style="font-weight:800;color:#1a6a40;font-size:15px">📄 Attestation de bénévolat</div>
      <div style="font-size:12px;color:#2a6a40;margin-top:3px;font-weight:600">Téléchargez et imprimez votre attestation officielle.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="<?php echo esc_url( $att_url ); ?>" target="_blank"
         style="background:#4caf7d;color:#fff;font-weight:800;font-size:13px;padding:9px 18px;border-radius:10px;text-decoration:none;white-space:nowrap;box-shadow:0 3px 10px rgba(76,175,125,.35)">
        Saison <?php echo esc_html( $year ); ?> ↓
      </a>
      <?php if ( $att_url_prev ) : ?>
      <a href="<?php echo esc_url( $att_url_prev ); ?>" target="_blank"
         style="background:#fff;color:#4caf7d;border:1.5px solid #4caf7d;font-size:12px;padding:9px 16px;border-radius:10px;text-decoration:none;white-space:nowrap;font-weight:700">
        Saison <?php echo esc_html( $year - 1 ); ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ BADGES ══ -->
  <?php if ( ! empty( $badges['earned'] ) || ! empty( $badges['locked'] ) ) : ?>
  <div style="margin-bottom:22px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <h3 style="font-size:15px;font-weight:800;color:#1e3040;margin:0">🏅 Mes badges</h3>
      <?php if ( ! empty( $badges['earned'] ) ) : ?>
      <span style="background:#fff3d0;color:#9a6f00;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;border:1px solid #ffd060">
        <?php echo esc_html( count( $badges['earned'] ) ); ?> / <?php echo esc_html( $total_badges ); ?> débloqué<?php echo count( $badges['earned'] ) > 1 ? 's' : ''; ?>
      </span>
      <?php endif; ?>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px">

      <!-- Badges débloqués -->
      <?php foreach ( $badges['earned'] as $key => $b ) : ?>
      <div style="background:#fff;border:2px solid #ffc107;border-radius:14px;padding:13px 8px;text-align:center;box-shadow:0 3px 12px rgba(255,193,7,.2);position:relative;transition:transform .15s" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform=''">
        <div style="position:absolute;top:-7px;right:-7px;background:#ffc107;color:#fff;font-size:9px;font-weight:900;padding:2px 6px;border-radius:20px;box-shadow:0 1px 4px rgba(0,0,0,.2)">✓</div>
        <div style="font-size:30px;margin-bottom:6px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.15))"><?php echo $b['icon']; // phpcs:ignore ?></div>
        <div style="font-size:11px;font-weight:800;color:#333;line-height:1.3"><?php echo esc_html( $b['name'] ); ?></div>
        <div style="font-size:9.5px;color:#888;margin-top:3px;line-height:1.3;font-weight:600"><?php echo esc_html( $b['desc'] ); ?></div>
      </div>
      <?php endforeach; ?>

      <!-- Badges verrouillés -->
      <?php foreach ( $badges['locked'] as $key => $b ) : ?>
      <div style="background:#f5f6f8;border:2px solid #e0e4e8;border-radius:14px;padding:13px 8px;text-align:center;position:relative">
        <div style="position:absolute;inset:0;background:rgba(255,255,255,.5);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#bbb">🔒</div>
        <div style="font-size:30px;margin-bottom:6px;filter:grayscale(1) blur(1.5px);opacity:.4"><?php echo $b['icon']; // phpcs:ignore ?></div>
        <div style="font-size:11px;font-weight:800;color:#bbb;line-height:1.3"><?php echo esc_html( $b['name'] ); ?></div>
        <div style="font-size:9.5px;color:#ccc;margin-top:3px;line-height:1.3;font-weight:600"><?php echo esc_html( $b['desc'] ); ?></div>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
  <?php endif; ?>

  <!-- ══ PROCHAINES PROSPECTIONS ══ -->
  <div style="margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <h3 style="font-size:15px;font-weight:800;color:#1e3040;margin:0">📅 Prochaines prospections</h3>
      <span style="background:#e3f2fd;color:<?php echo $color; ?>;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px"><?php echo esc_html( $nb_upcoming ); ?></span>
    </div>

    <?php if ( empty( $upcoming ) ) : ?>
      <div style="background:#f8f9fb;border:1.5px dashed #d0d8e0;border-radius:12px;padding:22px;text-align:center;color:#aaa;font-size:14px;font-weight:600">
        Aucune prospection à venir.<br>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo $color; ?>;font-weight:800;text-decoration:none">Voir le planning →</a>
      </div>
    <?php else : ?>
      <?php foreach ( $upcoming as $h ) : ?>
      <div style="background:#fff;border:1.5px solid #dce9f0;border-radius:14px;padding:16px 18px;margin-bottom:10px;box-shadow:0 2px 10px rgba(0,0,0,.04);display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
        <div style="flex:1;min-width:0">
          <div style="font-weight:800;font-size:15px;color:#1e3040;margin-bottom:4px">
            <?php echo esc_html( date_i18n( 'l d F Y', strtotime( $h->date ) ) ); ?>
          </div>
          <div style="font-size:13px;color:#6b7e8e;font-weight:600">
            🏖️ <?php echo esc_html( $h->zone_name ); ?> — <?php echo esc_html( $h->commune ); ?> ·
            ⏰ <?php echo esc_html( substr( $h->time_start, 0, 5 ) ); ?> ·
            <?php echo esc_html( $type_labels[ $h->type_prospect ] ?? $h->type_prospect ); ?>
          </div>
          <?php if ( 'waitlist' === $h->status ) : ?>
            <span style="display:inline-block;margin-top:6px;background:#fff3cd;color:#856404;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;border:1px solid #ffe082">⏳ Liste d'attente</span>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:7px;flex-wrap:wrap;flex-shrink:0">
          <?php if ( $h->token && 'waitlist' !== $h->status ) : ?>
          <a href="<?php echo esc_url( home_url( '?shl_terrain=' . rawurlencode( $h->token ) ) ); ?>"
             style="background:<?php echo $color; ?>;color:#fff;font-size:12px;font-weight:800;padding:8px 16px;border-radius:10px;text-decoration:none;white-space:nowrap;box-shadow:0 3px 10px rgba(46,134,171,.3)">
            🐢 Formulaire terrain
          </a>
          <?php endif; ?>
          <?php if ( $h->token && ! in_array( $h->status, array( 'cancelled', 'waitlist' ), true ) ) : ?>
          <a href="<?php echo esc_url( home_url( '?shl_cancel=' . rawurlencode( $h->token ) ) ); ?>"
             style="background:#fff;color:#e05555;border:1.5px solid #e05555;font-size:12px;padding:8px 14px;border-radius:10px;text-decoration:none;white-space:nowrap;font-weight:700"
             onclick="return confirm('Annuler cette inscription ?')">
            Annuler
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ══ TIMELINE PROSPECTIONS PASSÉES ══ -->
  <div style="margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
      <h3 style="font-size:15px;font-weight:800;color:#1e3040;margin:0">🏁 Mes prospections passées</h3>
      <span style="background:#f0f2f4;color:#6b7e8e;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px"><?php echo esc_html( $nb_done ); ?></span>
    </div>

    <?php if ( empty( $past ) ) : ?>
      <div style="background:#f8f9fb;border:1.5px dashed #d0d8e0;border-radius:12px;padding:22px;text-align:center;color:#aaa;font-size:14px;font-weight:600">
        Aucune prospection réalisée pour l'instant.
      </div>
    <?php else : ?>
    <!-- Timeline -->
    <div style="position:relative;padding-left:28px">
      <!-- Ligne verticale -->
      <div style="position:absolute;left:9px;top:8px;bottom:8px;width:2px;background:linear-gradient(to bottom,<?php echo $color; ?>,#dce9f0)"></div>

      <?php foreach ( $past as $i => $h ) :
        $actual = '';
        if ( $h->actual_time_start ) {
          $actual = substr( $h->actual_time_start, 0, 5 );
          if ( $h->actual_time_end ) $actual .= ' → ' . substr( $h->actual_time_end, 0, 5 );
        }
        $track = $tracks[ (int) $h->id ] ?? null;
        $has_obs = $h->obs_count > 0;
      ?>
      <div style="position:relative;margin-bottom:14px">
        <!-- Point timeline -->
        <div style="position:absolute;left:-22px;top:12px;width:12px;height:12px;border-radius:50%;background:<?php echo $i === 0 ? $color : '#dce9f0'; ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?php echo $i === 0 ? $color : '#dce9f0'; ?>"></div>

        <div style="background:#fff;border:1px solid #dce9f0;border-radius:12px;padding:13px 16px;box-shadow:0 2px 8px rgba(0,0,0,.04)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
            <div style="flex:1;min-width:0">
              <div style="font-size:13.5px;font-weight:800;color:#1e3040">
                <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->date ) ) ); ?> —
                <?php echo esc_html( $h->zone_name ); ?>
              </div>
              <div style="font-size:12px;color:#6b7e8e;margin-top:3px;font-weight:600;display:flex;flex-wrap:wrap;gap:8px">
                <span><?php echo esc_html( $type_labels[ $h->type_prospect ] ?? $h->type_prospect ); ?></span>
                <?php if ( $actual ) : ?><span>⏰ <?php echo esc_html( $actual ); ?></span><?php endif; ?>
                <?php if ( $has_obs ) : ?><span style="color:#9b7fd4">📸 <?php echo esc_html( $h->obs_count ); ?> obs.</span><?php endif; ?>
              </div>

              <?php if ( $track ) :
                $t_dist = $track->distance_m >= 1000
                  ? number_format( $track->distance_m / 1000, 2 ) . ' km'
                  : round( $track->distance_m ) . ' m';
                $t_h  = floor( $track->duration_s / 3600 );
                $t_m  = floor( ( $track->duration_s % 3600 ) / 60 );
                $t_dur = $t_h > 0 ? $t_h . 'h' . str_pad( $t_m, 2, '0', STR_PAD_LEFT ) : $t_m . 'min';
              ?>
              <div style="margin-top:6px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                <span style="font-size:11px;background:#e8f4ff;color:<?php echo $color; ?>;border-radius:20px;padding:3px 10px;font-weight:700">
                  🗺️ <?php echo esc_html( $t_dist ); ?> · <?php echo esc_html( $t_dur ); ?>
                </span>
                <button onclick="shlOpenTrack(<?php echo esc_js( $h->id ); ?>, <?php echo wp_json_encode( date_i18n( 'd/m/Y', strtotime( $h->date ) ) . ' – ' . $h->zone_name ); ?>)"
                        style="border:none;background:none;color:<?php echo $color; ?>;font-size:11px;font-weight:800;cursor:pointer;padding:2px 0;text-decoration:underline;font-family:inherit">
                  Voir le tracé →
                </button>
              </div>
              <?php endif; ?>
            </div>

            <?php if ( $h->token ) : ?>
            <a href="<?php echo esc_url( home_url( '?shl_terrain=' . rawurlencode( $h->token ) ) ); ?>"
               style="background:#f0f5fb;color:<?php echo $color; ?>;border:1px solid #d0e0ec;font-size:12px;font-weight:700;padding:6px 12px;border-radius:8px;text-decoration:none;white-space:nowrap;align-self:flex-start">
              Voir / compléter
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ LIENS UTILES ══ -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
    <div style="background:linear-gradient(135deg,#e8faf0,#d0f0e0);border:2px solid #25d366;border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:10px">
      <div>
        <div style="font-weight:800;color:#1a7a44;font-size:14px">💬 Groupe WhatsApp</div>
        <div style="font-size:11.5px;color:#2a8050;margin-top:2px;font-weight:600">Annonces, météo, infos terrain</div>
      </div>
      <a href="https://chat.whatsapp.com/E6XzXU3n86MDmIHpzJ7XvR" target="_blank" rel="noopener"
         style="background:#25d366;color:#fff;font-weight:800;font-size:13px;padding:9px 14px;border-radius:10px;text-decoration:none;text-align:center;box-shadow:0 3px 10px rgba(37,211,102,.3)">
        Rejoindre le groupe →
      </a>
    </div>
    <div style="background:linear-gradient(135deg,#e8f4ff,#d0e8f8);border:2px solid <?php echo $color; ?>;border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:10px">
      <div>
        <div style="font-weight:800;color:#1a5f7a;font-size:14px">📖 Guide bénévoles</div>
        <div style="font-size:11.5px;color:#2a6a8a;margin-top:2px;font-weight:600">Toutes les explications en ligne</div>
      </div>
      <a href="https://ashl.fr//wp-content/plugins/planning-prospections-tortues/GUIDE_BENEVOLES.html" target="_blank" rel="noopener"
         style="background:<?php echo $color; ?>;color:#fff;font-weight:800;font-size:13px;padding:9px 14px;border-radius:10px;text-decoration:none;text-align:center;box-shadow:0 3px 10px rgba(46,134,171,.3)">
        Consulter le guide →
      </a>
    </div>
  </div>

  <!-- Footer -->
  <div style="text-align:center;font-size:11px;color:#bbb;padding-bottom:24px;font-weight:600">
    Session active · <a href="<?php echo esc_url( $logout_url ); ?>" style="color:#bbb;text-decoration:underline">Se déconnecter</a>
  </div>

</div>

<?php if ( ! empty( $tracks ) ) : ?>
<!-- Modal tracé GPS -->
<div id="shl-track-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:9999;padding:16px;box-sizing:border-box;overflow-y:auto" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:#fff;border-radius:18px;max-width:620px;margin:0 auto;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.35)">
    <div style="padding:18px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,<?php echo $color; ?>,#1a5f7a)">
      <div>
        <div id="shl-modal-title" style="font-weight:800;font-size:16px;color:#fff;font-family:'Nunito',sans-serif"></div>
        <div id="shl-modal-stats" style="font-size:12px;color:rgba(255,255,255,.75);margin-top:3px;font-weight:600"></div>
      </div>
      <button onclick="document.getElementById('shl-track-modal').style.display='none'" style="border:none;background:rgba(255,255,255,.2);font-size:20px;cursor:pointer;color:#fff;line-height:1;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center">×</button>
    </div>
    <div id="shl-modal-map" style="height:340px"></div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var shlTracks = <?php
  $tracks_js = array();
  foreach ( $tracks as $rid => $t ) {
    $tracks_js[ $rid ] = array(
      'geojson'    => $t->geojson,
      'distance_m' => (float) $t->distance_m,
      'duration_s' => (int)   $t->duration_s,
      'started_at' => $t->started_at,
      'ended_at'   => $t->ended_at,
    );
  }
  echo wp_json_encode( $tracks_js );
?>;
var modalMap = null;
function shlOpenTrack(regId, title) {
  var t = shlTracks[regId];
  if (!t) return;
  var modal = document.getElementById('shl-track-modal');
  modal.style.display = 'block';
  var dist = t.distance_m < 1000 ? Math.round(t.distance_m) + ' m' : (t.distance_m/1000).toFixed(2) + ' km';
  var h = Math.floor(t.duration_s/3600), m = Math.floor((t.duration_s%3600)/60);
  var dur = h > 0 ? h+'h'+(m<10?'0':'')+m : m+'min';
  var times = (t.started_at && t.ended_at) ? ' · ' + t.started_at + ' → ' + t.ended_at : '';
  document.getElementById('shl-modal-title').textContent = title;
  document.getElementById('shl-modal-stats').textContent = '📍 ' + dist + ' · ⏱️ ' + dur + times;
  if (modalMap) { modalMap.remove(); modalMap = null; }
  setTimeout(function() {
    var data   = JSON.parse(t.geojson);
    var coords = data.coordinates.map(function(c){return [c[1],c[0]];});
    modalMap = L.map('shl-modal-map', { zoomControl: true, attributionControl: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(modalMap);
    var poly = L.polyline(coords, {color:'<?php echo esc_js($color); ?>',weight:5,opacity:.9}).addTo(modalMap);
    L.circleMarker(coords[0],               {radius:9,fillColor:'#27ae60',color:'#fff',weight:2,fillOpacity:1}).addTo(modalMap);
    L.circleMarker(coords[coords.length-1], {radius:9,fillColor:'#e05555',color:'#fff',weight:2,fillOpacity:1}).addTo(modalMap);
    modalMap.fitBounds(poly.getBounds(),{padding:[20,20]});
  }, 100);
}
</script>
<?php endif; ?>
