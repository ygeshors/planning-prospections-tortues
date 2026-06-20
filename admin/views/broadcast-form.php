<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">📢</span>
    <div>
      <h1>Message aux inscrits</h1>
      <p class="shl-subtitle">
        <?php echo esc_html( $slot->zone_name ); ?> &ndash;
        <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ); ?> &ndash;
        <?php echo count( $recipients ); ?> destinataire(s)
      </p>
    </div>
  </div>

  <p style="margin-bottom:20px">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot_id ) ); ?>"
       class="shl-btn shl-btn-ghost">← Retour aux inscrits</a>
  </p>

  <?php if ( empty( $recipients ) ) : ?>
    <div class="notice notice-warning"><p>Aucun inscrit actif sur ce créneau. Impossible d'envoyer un message.</p></div>
  <?php else : ?>

  <div class="shl-card" style="max-width:680px">

    <div style="background:#f0f6fb;border-left:4px solid #2E86AB;padding:12px 16px;border-radius:0 6px 6px 0;margin-bottom:20px;font-size:13px">
      <strong>Destinataires :</strong>
      <?php echo esc_html( implode( ', ', array_map( static function ( $r ) {
          return $r->firstname . ' ' . $r->lastname;
      }, array_values( $recipients ) ) ) ); ?>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <input type="hidden" name="action"  value="shl_broadcast">
      <input type="hidden" name="slot_id" value="<?php echo esc_attr( $slot_id ); ?>">
      <?php wp_nonce_field( 'shl_broadcast_' . $slot_id ); ?>

      <div style="margin-bottom:16px">
        <label for="bc_subject" style="font-weight:600;display:block;margin-bottom:6px">Objet du message *</label>
        <input type="text" id="bc_subject" name="bc_subject" required
               style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px"
               placeholder="Ex&nbsp;: Annulation météo – Prospection du <?php echo esc_attr( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ); ?>">
      </div>

      <div style="margin-bottom:24px">
        <label for="bc_message" style="font-weight:600;display:block;margin-bottom:6px">Message *</label>
        <textarea id="bc_message" name="bc_message" required rows="10"
                  style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:inherit;resize:vertical"
                  placeholder="Bonjour,&#10;&#10;En raison des conditions météorologiques, la prospection de demain est annulée.&#10;&#10;Nous vous contacterons dès que possible pour la prochaine date.&#10;&#10;Merci de votre compréhension.&#10;L'équipe SHL"></textarea>
        <p style="font-size:12px;color:#888;margin:4px 0 0">
          L'email sera envoyé individuellement (avec le prénom de chaque bénévole) depuis l'adresse de l'association.
        </p>
      </div>

      <div style="display:flex;gap:12px;align-items:center">
        <button type="submit" class="shl-btn shl-btn-primary"
                onclick="return confirm('Envoyer ce message à <?php echo count( $recipients ); ?> bénévole(s) ? Cette action est irréversible.')">
          📢 Envoyer à <?php echo count( $recipients ); ?> bénévole(s)
        </button>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot_id ) ); ?>"
           class="shl-btn shl-btn-ghost">Annuler</a>
      </div>

    </form>
  </div>
  <?php endif; ?>

</div>
