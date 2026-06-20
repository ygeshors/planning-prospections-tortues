<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables disponibles : $reg, $slot, $cancelled, $already, $invalid, $error, $token
$color = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$site  = esc_url( home_url() );
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Annulation – Prospections Tortues Marines</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0; padding: 20px;
      background: #0d1b2a; color: #e8f0f7;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
    }
    .box {
      background: #1a2f45; border-radius: 16px;
      padding: 36px 32px; max-width: 440px; width: 100%;
      text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,.4);
    }
    .icon { font-size: 52px; margin-bottom: 16px; }
    h1 { font-size: 22px; margin: 0 0 8px; color: #e8f0f7; }
    .subtitle { color: #8a9ab0; font-size: 14px; margin: 0 0 24px; line-height: 1.5; }
    .info-card {
      background: #0d1b2a; border-radius: 10px;
      padding: 16px 20px; margin: 20px 0; text-align: left;
    }
    .info-row { display: flex; gap: 8px; margin-bottom: 6px; font-size: 14px; }
    .info-label { color: #8a9ab0; min-width: 80px; }
    .info-value { color: #e8f0f7; font-weight: 600; }
    .btn {
      display: inline-block; width: 100%; padding: 14px 20px;
      border-radius: 10px; border: none; font-size: 15px; font-weight: 700;
      cursor: pointer; text-decoration: none; margin-top: 8px;
    }
    .btn-danger { background: #e05555; color: #fff; }
    .btn-danger:hover { background: #c94040; }
    .btn-ghost {
      background: transparent; color: #8a9ab0;
      border: 1px solid #2a4060; font-size: 14px;
    }
    .btn-ghost:hover { border-color: #4ECDC4; color: #4ECDC4; }
    .btn-primary { background: <?php echo $color; ?>; color: #fff; }
    .notice-error { background: rgba(224,85,85,.15); border: 1px solid #e05555;
      border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;
      color: #ff8080; font-size: 14px; }
    .notice-success { background: rgba(78,205,196,.12); border: 1px solid #4ECDC4;
      border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;
      color: #4ECDC4; font-size: 14px; }
    footer { margin-top: 24px; font-size: 12px; color: #4a6080; }
  </style>
</head>
<body>
<div class="box">

<?php if ( $invalid ) : ?>
  <div class="icon">🔒</div>
  <h1>Lien invalide</h1>
  <p class="subtitle">Ce lien d'annulation n'est pas valide ou a déjà été utilisé.<br>Vérifiez l'email de confirmation reçu lors de votre inscription.</p>
  <a href="<?php echo $site; ?>" class="btn btn-primary">← Retour au site</a>

<?php elseif ( $cancelled ) : ?>
  <div class="icon">✅</div>
  <h1>Inscription annulée</h1>
  <p class="subtitle">Votre inscription à la prospection du <strong><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ); ?></strong> sur <strong><?php echo esc_html( $slot->zone_name ); ?></strong> a bien été annulée.<br>La place a été libérée pour d'autres bénévoles.</p>
  <p class="notice-success">Un email de confirmation vous a été envoyé.</p>
  <a href="<?php echo $site; ?>" class="btn btn-ghost">← Retour au planning</a>

<?php elseif ( $already ) : ?>
  <div class="icon">ℹ️</div>
  <h1>Déjà annulée</h1>
  <p class="subtitle">Cette inscription a déjà été annulée ou refusée.</p>
  <a href="<?php echo $site; ?>" class="btn btn-ghost">← Retour au planning</a>

<?php else : ?>
  <div class="icon">🐢</div>
  <h1>Annuler mon inscription</h1>
  <p class="subtitle">Vous êtes sur le point d'annuler votre inscription à :</p>

  <?php if ( $slot ) : ?>
  <div class="info-card">
    <div class="info-row"><span class="info-label">📅 Date</span><span class="info-value"><?php echo esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ); ?></span></div>
    <div class="info-row"><span class="info-label">🏖️ Plage</span><span class="info-value"><?php echo esc_html( $slot->zone_name ); ?> – <?php echo esc_html( $slot->commune ); ?></span></div>
    <div class="info-row"><span class="info-label">⏰ Heure</span><span class="info-value"><?php echo esc_html( substr( $slot->time_start, 0, 5 ) ); ?></span></div>
    <div class="info-row"><span class="info-label">👤 Prénom</span><span class="info-value"><?php echo esc_html( $reg->firstname . ' ' . $reg->lastname ); ?></span></div>
  </div>
  <?php endif; ?>

  <?php if ( $error ) : ?>
    <div class="notice-error"><?php echo esc_html( $error ); ?></div>
  <?php endif; ?>

  <form method="post">
    <?php wp_nonce_field( 'shl_cancel_' . $token, 'shl_cancel_nonce' ); ?>
    <button type="submit" class="btn btn-danger">✗ Oui, annuler mon inscription</button>
  </form>
  <a href="<?php echo esc_url( home_url() . '?shl_terrain=' . rawurlencode( $token ) ); ?>" class="btn btn-ghost" style="margin-top:10px">
    ← Non, je garde mon inscription
  </a>
<?php endif; ?>

  <footer>
    🐢 Planning Prospections Tortues Marines – Sauvegarde Hérault Littoral<br>
    <a href="tel:0423500338" style="color:#8a9ab0;text-decoration:none">04 23 50 03 38</a>
    &nbsp;·&nbsp;
    RTMMF : <a href="tel:0616862686" style="color:#8a9ab0;text-decoration:none">06 16 86 26 86</a>
  </footer>
</div>
</body>
</html>
