<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Variables disponibles : $zones (liste des zones WP_Object[])
$color       = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
$type_labels = array( 'foot' => '🚶 À pied', 'drone' => '🚁 Drone', 'mixed' => '🔀 Mixte (pied + drone)' );
?>
<div class="shl-planning shl-libre-wrap">

  <div class="shl-libre-header">
    <span class="shl-libre-icon">🐢</span>
    <div>
      <h2 class="shl-libre-title">Déclarer une prospection</h2>
      <p class="shl-libre-subtitle">Hors planning – sur l'initiative du bénévole</p>
    </div>
  </div>

  <!-- Alerte : déclaration obligatoire la veille -->
  <div class="shl-libre-alert">
    <span class="shl-libre-alert-icon">⚠️</span>
    <div>
      <strong>Déclaration obligatoire la veille au plus tard</strong>
      Toute prospection hors planning doit être déclarée <strong>au plus tard la veille</strong> de votre sortie terrain.
      En cas de doute, contactez l'association avant de partir.
    </div>
  </div>

  <div id="shl-libre-success" class="shl-libre-success" style="display:none">
    <div class="shl-libre-success-icon">✅</div>
    <p id="shl-libre-success-msg"></p>
    <p class="shl-libre-success-sub">Merci pour votre engagement bénévole !</p>
  </div>

  <form id="shl-libre-form" class="shl-libre-form" novalidate>

    <!-- Section 1 : vos coordonnées -->
    <fieldset class="shl-libre-section">
      <legend>👤 Vos coordonnées</legend>
      <div class="shl-libre-row">
        <div class="shl-libre-field">
          <label for="shl-libre-firstname">Prénom <span class="shl-req">*</span></label>
          <input type="text" id="shl-libre-firstname" name="firstname" required autocomplete="given-name">
        </div>
        <div class="shl-libre-field">
          <label for="shl-libre-lastname">Nom <span class="shl-req">*</span></label>
          <input type="text" id="shl-libre-lastname" name="lastname" required autocomplete="family-name">
        </div>
      </div>
      <div class="shl-libre-row">
        <div class="shl-libre-field">
          <label for="shl-libre-email">Email <span class="shl-req">*</span></label>
          <input type="email" id="shl-libre-email" name="email" required autocomplete="email">
        </div>
        <div class="shl-libre-field">
          <label for="shl-libre-phone">Téléphone</label>
          <input type="tel" id="shl-libre-phone" name="phone" autocomplete="tel">
        </div>
      </div>
      <label class="shl-libre-check">
        <input type="checkbox" name="is_member" value="1">
        <span>Je suis adhérent(e) de Sauvegarde Hérault Littoral</span>
      </label>
    </fieldset>

    <!-- Section 2 : la prospection -->
    <fieldset class="shl-libre-section">
      <legend>🏖️ La prospection</legend>
      <div class="shl-libre-row">
        <div class="shl-libre-field">
          <label for="shl-libre-date">Date de la prospection <span class="shl-req">*</span></label>
          <input type="date" id="shl-libre-date" name="date_prospect" required
                 min="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
                 value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>">
          <small style="color:#7a5000;font-size:12px">Minimum : demain (déclaration la veille au plus tard)</small>
        </div>
      </div>
      <div class="shl-libre-row">
        <div class="shl-libre-field">
          <label for="shl-libre-tstart">Heure de début <span class="shl-req">*</span></label>
          <input type="time" id="shl-libre-tstart" name="time_start" required>
        </div>
        <div class="shl-libre-field">
          <label for="shl-libre-tend">Heure de fin</label>
          <input type="time" id="shl-libre-tend" name="time_end">
        </div>
      </div>
      <div class="shl-libre-row">
        <div class="shl-libre-field shl-libre-field-full">
          <label for="shl-libre-zone">Plage / Secteur <span class="shl-req">*</span></label>
          <input type="text" id="shl-libre-zone" name="zone_name" required
                 list="shl-libre-zones-list" placeholder="Ex : Plage de la Tamarissière"
                 autocomplete="off">
          <?php if ( ! empty( $zones ) ) : ?>
          <datalist id="shl-libre-zones-list">
            <?php foreach ( $zones as $z ) : ?>
              <option value="<?php echo esc_attr( $z->name ); ?>" data-commune="<?php echo esc_attr( $z->commune ); ?>">
            <?php endforeach; ?>
          </datalist>
          <?php endif; ?>
        </div>
      </div>
      <div class="shl-libre-row">
        <div class="shl-libre-field shl-libre-field-full">
          <label for="shl-libre-commune">Commune <span class="shl-req">*</span></label>
          <input type="text" id="shl-libre-commune" name="commune" required
                 placeholder="Ex : Agde">
        </div>
      </div>
      <div class="shl-libre-field">
        <label>Type de prospection <span class="shl-req">*</span></label>
        <div class="shl-libre-type-grid">
          <?php foreach ( $type_labels as $val => $lbl ) : ?>
          <label class="shl-libre-type-card">
            <input type="radio" name="type_prospect" value="<?php echo esc_attr( $val ); ?>"
                   <?php echo 'foot' === $val ? 'checked' : ''; ?> required>
            <span><?php echo esc_html( $lbl ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </fieldset>

    <!-- Section 3 : résultat -->
    <fieldset class="shl-libre-section">
      <legend>📋 Résultat observé</legend>
      <div class="shl-libre-obs-grid">
        <label class="shl-libre-obs-card">
          <input type="radio" name="obs_type" value="none" checked>
          <span class="shl-libre-obs-icon">✅</span>
          <span>Aucune trace</span>
        </label>
        <label class="shl-libre-obs-card">
          <input type="radio" name="obs_type" value="suspect">
          <span class="shl-libre-obs-icon">⚠️</span>
          <span>Trace suspecte</span>
        </label>
        <label class="shl-libre-obs-card">
          <input type="radio" name="obs_type" value="confirmed">
          <span class="shl-libre-obs-icon">🐢</span>
          <span>Trace confirmée !</span>
        </label>
        <label class="shl-libre-obs-card">
          <input type="radio" name="obs_type" value="other">
          <span class="shl-libre-obs-icon">👁️</span>
          <span>Autre observation</span>
        </label>
      </div>
      <div class="shl-libre-field" style="margin-top:14px">
        <label for="shl-libre-comment">Commentaire / description</label>
        <textarea id="shl-libre-comment" name="comment" rows="3"
                  placeholder="Décrivez vos observations : position, taille, état, météo, faune associée…"></textarea>
      </div>
    </fieldset>

    <!-- Section 4 : règles -->
    <fieldset class="shl-libre-section">
      <legend>📋 Consignes</legend>
      <div class="shl-libre-consignes">
        <?php echo nl2br( esc_html( get_option( 'shl_tortues_general_instructions', '' ) ) ); ?>
      </div>
      <label class="shl-libre-check shl-libre-check-rules">
        <input type="checkbox" name="accepted_rules" value="1" required id="shl-libre-rules">
        <span>J'ai respecté les consignes de prospection <span class="shl-req">*</span></span>
      </label>
    </fieldset>

    <div id="shl-libre-error" class="shl-libre-notice shl-libre-notice-error" style="display:none"></div>

    <button type="submit" class="shl-libre-submit" id="shl-libre-submit">
      <span id="shl-libre-submit-text">📤 Enregistrer ma prospection</span>
    </button>

  </form>
</div>

  <!-- Contacts en bas de formulaire -->
  <div class="shl-libre-contacts">
    <div>
      <strong>🐢 Sauvegarde Hérault Littoral</strong>
      <a href="tel:0423500338">04 23 50 03 38</a>
    </div>
    <div>
      <strong>📞 Réseau Tortues Marines Méditerranée France</strong>
      <a href="tel:0616862686">06 16 86 26 86</a>
    </div>
    <div style="font-size:12px;color:#9a6070;margin-top:2px">
      En cas d'observation de tortue en détresse, contactez immédiatement le RTMMF
    </div>
  </div>

<script>
(function(){
  var form   = document.getElementById('shl-libre-form');
  var errEl  = document.getElementById('shl-libre-error');
  var succEl = document.getElementById('shl-libre-success');
  var msgEl  = document.getElementById('shl-libre-success-msg');
  var btn    = document.getElementById('shl-libre-submit');
  var btnTxt = document.getElementById('shl-libre-submit-text');

  // Auto-remplir la commune depuis la datalist
  document.getElementById('shl-libre-zone').addEventListener('input', function(){
    var val = this.value;
    var opts = document.querySelectorAll('#shl-libre-zones-list option');
    opts.forEach(function(o){
      if (o.value === val && o.dataset.commune) {
        document.getElementById('shl-libre-commune').value = o.dataset.commune;
      }
    });
  });

  // Style tactile des radios
  document.querySelectorAll('.shl-libre-obs-card, .shl-libre-type-card').forEach(function(label){
    var inp = label.querySelector('input[type="radio"]');
    inp.addEventListener('change', function(){
      var name = this.name;
      document.querySelectorAll('input[name="' + name + '"]').forEach(function(i){
        i.closest('label').classList.toggle('selected', i.checked);
      });
    });
    if (inp.checked) { label.classList.add('selected'); }
  });

  form.addEventListener('submit', function(e){
    e.preventDefault();
    if (errEl) { errEl.style.display = 'none'; }
    btn.disabled = true;
    btnTxt.textContent = '⏳ Envoi en cours…';

    var data = new FormData(form);
    data.append('action', 'shl_submit_libre');
    data.append('nonce',  (window.shlLibre || {}).nonce || '');

    fetch((window.shlLibre || {}).ajax || '', { method: 'POST', body: data })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (res.success) {
          form.style.display = 'none';
          msgEl.textContent  = res.data.message || 'Prospection enregistrée !';
          succEl.style.display = 'block';
        } else {
          errEl.textContent    = res.data || 'Erreur inconnue.';
          errEl.style.display  = 'block';
          btn.disabled         = false;
          btnTxt.textContent   = '📤 Enregistrer ma prospection';
        }
      })
      .catch(function(){
        errEl.textContent   = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
        errEl.style.display = 'block';
        btn.disabled        = false;
        btnTxt.textContent  = '📤 Enregistrer ma prospection';
      });
  });
})();
</script>
