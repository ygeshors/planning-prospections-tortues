<?php if ( ! defined( 'ABSPATH' ) ) exit;
// Variables : $error (string, optionnel)
$color      = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$color_dark = '#1a6890';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap');

.shl-login-wrap * { box-sizing: border-box; }

.shl-login-wrap {
  font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  max-width: 480px;
  margin: 0 auto;
  padding: 0 12px 40px;
  -webkit-font-smoothing: antialiased;
}

/* ── Header ── */
.shl-login-hero {
  background: linear-gradient(135deg, <?php echo $color; ?> 0%, <?php echo $color_dark; ?> 100%);
  border-radius: 20px 20px 0 0;
  padding: 36px 32px 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.shl-login-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E") repeat;
  pointer-events: none;
}

.shl-login-turtle {
  font-size: 60px;
  line-height: 1;
  display: block;
  margin-bottom: 14px;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,.25));
  position: relative;
}

.shl-login-title {
  color: #fff;
  font-size: 22px;
  font-weight: 900;
  margin: 0 0 6px;
  position: relative;
}

.shl-login-org {
  color: rgba(255,255,255,.78);
  font-size: 13px;
  font-weight: 600;
  margin: 0 0 28px;
  position: relative;
}

.shl-login-wave {
  display: block;
  width: calc(100% + 2px);
  margin: 0 -1px -1px;
  height: 32px;
  position: relative;
}

/* ── Carte principale ── */
.shl-login-card {
  background: #fff;
  border: 1px solid #dce9f0;
  border-top: none;
  border-radius: 0 0 20px 20px;
  padding: 28px 28px 24px;
  box-shadow: 0 10px 32px rgba(46,134,171,.12);
}

/* ── Erreur ── */
.shl-login-error {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff8e1;
  border: 1px solid #ffe082;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 20px;
  font-size: 13px;
  color: #6d4c00;
  font-weight: 600;
}

/* ── Description ── */
.shl-login-desc {
  font-size: 14px;
  color: #4a6275;
  line-height: 1.65;
  margin: 0 0 22px;
  font-weight: 600;
}

/* ── Formulaire ── */
.shl-login-field {
  position: relative;
  margin-bottom: 12px;
}

.shl-login-field input[type="email"] {
  width: 100%;
  padding: 14px 46px 14px 16px;
  border: 2px solid #d0e0ec;
  border-radius: 12px;
  font-size: 15px;
  font-family: inherit;
  font-weight: 600;
  color: #1e3040;
  transition: border-color .18s, box-shadow .18s;
  outline: none;
  background: #f8fbfd;
}

.shl-login-field input[type="email"]:focus {
  border-color: <?php echo $color; ?>;
  background: #fff;
  box-shadow: 0 0 0 3px <?php echo $color; ?>22;
}

.shl-login-field input[type="email"]::placeholder {
  color: #a0b8c8;
  font-weight: 600;
}

.shl-login-field-icon {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
  pointer-events: none;
}

.shl-login-btn {
  width: 100%;
  background: linear-gradient(135deg, <?php echo $color; ?>, <?php echo $color_dark; ?>);
  color: #fff;
  border: none;
  padding: 15px 20px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 800;
  font-family: inherit;
  cursor: pointer;
  transition: all .18s;
  box-shadow: 0 4px 16px <?php echo $color; ?>44;
  letter-spacing: .1px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.shl-login-btn:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px <?php echo $color; ?>55; }
.shl-login-btn:active { transform: scale(.98); }
.shl-login-btn:disabled { background: #b0c8d4; box-shadow: none; cursor: not-allowed; transform: none; }

/* ── Message de retour ── */
.shl-login-msg {
  display: none;
  margin-top: 14px;
  padding: 13px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.6;
}

.shl-login-msg-success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #2a6a40; }
.shl-login-msg-error   { background: #fdecea; border: 1px solid #f5c6cb; color: #721c24; }

/* ── Note spam ── */
.shl-login-spam-note {
  text-align: center;
  font-size: 11px;
  color: #a0b8c8;
  margin: 12px 0 0;
  font-weight: 600;
}

/* ── Séparateur ── */
.shl-login-sep {
  border: none;
  border-top: 1px solid #e8ecf0;
  margin: 22px 0;
}

/* ── Boutons CTA (WhatsApp, guide) ── */
.shl-login-ctas {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.shl-login-cta {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  border-radius: 12px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 800;
  transition: all .15s;
}

.shl-login-cta-wa {
  background: #e8faf0;
  color: #1a6a35;
  border: 1.5px solid #a8d8b8;
}

.shl-login-cta-wa:hover { background: #25d366; color: #fff; border-color: #25d366; }

.shl-login-cta-guide {
  background: <?php echo $color; ?>12;
  color: <?php echo $color_dark; ?>;
  border: 1.5px solid <?php echo $color; ?>30;
}

.shl-login-cta-guide:hover { background: <?php echo $color; ?>; color: #fff; border-color: <?php echo $color; ?>; }

.shl-login-cta-icon { font-size: 22px; flex-shrink: 0; }
.shl-login-cta-text strong { display: block; }
.shl-login-cta-text span   { font-size: 11px; font-weight: 600; opacity: .72; }
</style>

<div class="shl-login-wrap">

  <!-- En-tête avec vague -->
  <div class="shl-login-hero">
    <span class="shl-login-turtle">🐢</span>
    <h2 class="shl-login-title">Espace bénévole</h2>
    <p class="shl-login-org">Sauvegarde Hérault Littoral</p>
    <svg class="shl-login-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 32" preserveAspectRatio="none">
      <path d="M0,16 C80,32 160,0 240,16 C320,32 400,0 480,16 L480,32 L0,32 Z" fill="#ffffff"/>
    </svg>
  </div>

  <!-- Carte formulaire -->
  <div class="shl-login-card">

    <?php if ( ! empty( $error ) ) : ?>
    <div class="shl-login-error">
      ⚠️ <?php echo esc_html( $error ); ?>
    </div>
    <?php endif; ?>

    <p class="shl-login-desc">
      Entrez votre adresse email pour recevoir un <strong>lien de connexion sécurisé</strong>
      valable 24 heures. Aucun mot de passe nécessaire.
    </p>

    <div id="shl-bp-form">
      <div class="shl-login-field">
        <input type="email" id="shl-bp-email" placeholder="votre@email.fr" autocomplete="email">
        <span class="shl-login-field-icon">✉️</span>
      </div>
      <button id="shl-bp-submit" class="shl-login-btn">
        🔑 Recevoir mon lien de connexion
      </button>
    </div>

    <div id="shl-bp-msg" class="shl-login-msg"></div>

    <p class="shl-login-spam-note">
      Vérifiez le dossier <strong>spam</strong> si vous ne recevez rien dans 2 minutes.
    </p>

    <hr class="shl-login-sep">

    <div class="shl-login-ctas">
      <a href="https://chat.whatsapp.com/E6XzXU3n86MDmIHpzJ7XvR" target="_blank" rel="noopener"
         class="shl-login-cta shl-login-cta-wa">
        <span class="shl-login-cta-icon">💬</span>
        <div class="shl-login-cta-text">
          <strong>Rejoindre le groupe WhatsApp</strong>
          <span>Bénévoles prospections tortues marines</span>
        </div>
      </a>
      <a href="https://ashl.fr//wp-content/plugins/planning-prospections-tortues/GUIDE_BENEVOLES.html"
         target="_blank" rel="noopener"
         class="shl-login-cta shl-login-cta-guide">
        <span class="shl-login-cta-icon">📖</span>
        <div class="shl-login-cta-text">
          <strong>Guide des bénévoles</strong>
          <span>Consignes, protocoles et informations pratiques</span>
        </div>
      </a>
    </div>

  </div>

</div>

<script>
(function() {
  var btn   = document.getElementById('shl-bp-submit');
  var input = document.getElementById('shl-bp-email');
  var msg   = document.getElementById('shl-bp-msg');

  if (!btn || !input) { return; }

  btn.addEventListener('click', function() {
    var email = input.value.trim();
    if (!email || !/\S+@\S+\.\S+/.test(email)) {
      show('Veuillez saisir une adresse email valide.', 'error');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '⏳ Envoi en cours…';

    var data = new FormData();
    data.append('action',     'shl_send_magic_link');
    data.append('nonce',      <?php echo wp_json_encode( wp_create_nonce( 'shl_magic_nonce' ) ); ?>);
    data.append('email',      email);
    data.append('portal_url', window.location.href.split('?')[0]);

    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(r) {
        if (r.success) {
          show(r.data.message, 'success');
          document.getElementById('shl-bp-form').style.opacity = '.35';
          document.getElementById('shl-bp-form').style.pointerEvents = 'none';
        } else {
          show(r.data.message, 'error');
          btn.disabled = false;
          btn.innerHTML = '🔑 Recevoir mon lien de connexion';
        }
      })
      .catch(function() {
        show('Une erreur est survenue. Réessayez.', 'error');
        btn.disabled = false;
        btn.innerHTML = '🔑 Recevoir mon lien de connexion';
      });
  });

  input.addEventListener('keydown', function(e) { if (e.key === 'Enter') { btn.click(); } });

  function show(text, type) {
    msg.textContent = text;
    msg.className = 'shl-login-msg shl-login-msg-' + type;
    msg.style.display = 'block';
  }
})();
</script>
