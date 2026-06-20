<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$is_edit = ! is_null( $slot );
$title   = $is_edit ? 'Modifier le créneau' : 'Nouveau créneau';
$msg     = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';

$v = array(
	'id'             => $is_edit ? $slot->id : 0,
	'date'           => $is_edit ? $slot->date : date( 'Y-m-d' ),
	'time_start'     => $is_edit ? substr( $slot->time_start, 0, 5 ) : '08:00',
	'time_end'       => $is_edit && $slot->time_end ? substr( $slot->time_end, 0, 5 ) : '',
	'zone_id'        => $is_edit ? $slot->zone_id : '',
	'zone_name'      => $is_edit ? $slot->zone_name : '',
	'commune'        => $is_edit ? $slot->commune : '',
	'meeting_point'  => $is_edit ? $slot->meeting_point : '',
	'latitude'       => $is_edit ? $slot->latitude : '',
	'longitude'      => $is_edit ? $slot->longitude : '',
	'type_prospect'  => $is_edit ? $slot->type_prospect : 'foot',
	'places_total'   => $is_edit ? $slot->places_total : $default_places,
	'status'         => $is_edit ? $slot->status : 'open',
	'instructions'   => $is_edit ? $slot->instructions : '',
	'referent'       => $is_edit ? $slot->referent : '',
	'result'         => $is_edit ? $slot->result : '',
	'result_comment' => $is_edit ? $slot->result_comment : '',
);
?>
<style>
.shl-zone-checklist{border:1px solid #ddd;border-radius:6px;padding:6px 12px;max-height:220px;overflow-y:auto;background:#fafafa;margin-top:4px}
.shl-zone-check-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #eee;cursor:pointer}
.shl-zone-check-item:last-child{border-bottom:none}
.shl-zone-check-item input[type="checkbox"]{width:18px;height:18px;cursor:pointer;flex-shrink:0;accent-color:#2E86AB}
.shl-zone-check-label{font-size:13.5px;line-height:1.3}
.shl-zone-check-label small{color:#777;font-size:12px}
.shl-btn-link{background:none;border:none;color:#2271b1;cursor:pointer;padding:0;font-size:13px;text-decoration:underline}
.shl-btn-link:hover{color:#135e96}
.shl-free-zone-box{background:#f6f7f7;border:1px dashed #c3c4c7;border-radius:6px;padding:14px;margin-top:10px}
.shl-free-zone-box p{margin:0 0 10px;font-size:13px;color:#666}
.shl-zone-free-toggle{margin-top:8px}
.shl-mode-radio-group{display:flex;gap:12px;flex-wrap:wrap;margin-top:6px}
.shl-mode-radio{display:flex;align-items:center;gap:7px;cursor:pointer;padding:9px 16px;border-radius:8px;border:2px solid #ddd;background:#fafafa;font-size:14px;font-weight:500;transition:border-color .15s,background .15s}
.shl-mode-radio.active{border-color:#2E86AB;background:#e8f4f8}
.shl-mode-radio input{margin:0}
.shl-repeat-box{background:#f0f6fb;border:1px solid #c8dff0;border-radius:8px;padding:16px;margin-top:4px}
.shl-weekday-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.shl-weekday-btn{cursor:pointer;user-select:none}
.shl-weekday-btn input{position:absolute;opacity:0;width:0;height:0}
.shl-wd-label{display:inline-block;width:48px;text-align:center;padding:9px 4px;border-radius:8px;font-size:13px;font-weight:600;border:2px solid #ddd;background:#fff;transition:all .15s;cursor:pointer}
.shl-weekday-btn input:checked + .shl-wd-label{background:#2E86AB;color:#fff;border-color:#2E86AB}
.shl-wd-label:hover{border-color:#2E86AB}
.shl-slot-preview{margin-top:14px;padding:10px 16px;border-radius:8px;background:#d4edda;border:1px solid #c3e6cb;color:#155724;font-size:14px}
</style>

<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon"><?php echo $is_edit ? '✏️' : '➕'; ?></span>
    <div>
      <h1><?php echo esc_html( $title ); ?></h1>
      <p class="shl-subtitle"><a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots' ) ); ?>">← Retour à la liste</a></p>
    </div>
  </div>

  <?php if ( 'missing' === $msg ) : ?>
    <div class="notice notice-error is-dismissible"><p>Champs obligatoires manquants ou plage de dates invalide. Vérifiez tous les champs requis.</p></div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots&action=save' ) ); ?>" class="shl-form" id="shl-slot-form">
    <?php wp_nonce_field( 'shl_slot_save' ); ?>
    <input type="hidden" name="slot_id" value="<?php echo esc_attr( $v['id'] ); ?>">

    <div class="shl-form-grid">

      <!-- Colonne gauche -->
      <div class="shl-form-col">
        <div class="shl-card">
          <h2 class="shl-card-title">📍 Lieu et horaires</h2>

          <?php if ( $is_edit ) : ?>
          <!-- ── ÉDITION : sélecteur zone unique ── -->
          <div class="shl-field-group">
            <label>Zone de prospection (liste)</label>
            <select id="shl-zone-select" onchange="shlFillZone(this)">
              <option value="">— Sélectionner une zone prédéfinie —</option>
              <?php foreach ( $zones as $z ) : ?>
                <option value="<?php echo esc_attr( $z->id ); ?>"
                  data-name="<?php echo esc_attr( $z->name ); ?>"
                  data-commune="<?php echo esc_attr( $z->commune ); ?>"
                  data-lat="<?php echo esc_attr( $z->gps_lat ); ?>"
                  data-lng="<?php echo esc_attr( $z->gps_lng ); ?>"
                  <?php selected( $v['zone_id'], $z->id ); ?>>
                  <?php echo esc_html( $z->name . ' – ' . $z->commune ); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="zone_id" id="shl-zone-id" value="<?php echo esc_attr( $v['zone_id'] ); ?>">
          </div>

          <div class="shl-field-row">
            <div class="shl-field-group">
              <label for="zone_name">Plage / Secteur <span class="required">*</span></label>
              <input type="text" name="zone_name" id="zone_name" value="<?php echo esc_attr( $v['zone_name'] ); ?>" required placeholder="ex : Plage de la Tamarissière">
            </div>
            <div class="shl-field-group">
              <label for="commune">Commune <span class="required">*</span></label>
              <input type="text" name="commune" id="commune" value="<?php echo esc_attr( $v['commune'] ); ?>" required placeholder="ex : Agde">
            </div>
          </div>

          <div class="shl-field-group">
            <label for="meeting_point">Point de rendez-vous</label>
            <input type="text" name="meeting_point" id="meeting_point" value="<?php echo esc_attr( $v['meeting_point'] ); ?>" placeholder="ex : Parking du Grau d'Agde, entrée Est">
          </div>

          <div class="shl-field-row">
            <div class="shl-field-group">
              <label for="latitude">Latitude (GPS)</label>
              <input type="text" name="latitude" id="latitude" value="<?php echo esc_attr( $v['latitude'] ); ?>" placeholder="43.2965" pattern="[-0-9.]+">
            </div>
            <div class="shl-field-group">
              <label for="longitude">Longitude (GPS)</label>
              <input type="text" name="longitude" id="longitude" value="<?php echo esc_attr( $v['longitude'] ); ?>" placeholder="3.4752" pattern="[-0-9.]+">
            </div>
          </div>

          <div class="shl-field-row">
            <div class="shl-field-group">
              <label for="date">Date <span class="required">*</span></label>
              <input type="date" name="date" id="date" value="<?php echo esc_attr( $v['date'] ); ?>" required>
            </div>
            <div class="shl-field-group">
              <label for="time_start">Heure départ <span class="required">*</span></label>
              <input type="time" name="time_start" id="time_start" value="<?php echo esc_attr( $v['time_start'] ); ?>" required>
            </div>
            <div class="shl-field-group">
              <label for="time_end">Heure fin (optionnel)</label>
              <input type="time" name="time_end" id="time_end" value="<?php echo esc_attr( $v['time_end'] ); ?>">
            </div>
          </div>

          <?php else : ?>
          <!-- ── CRÉATION : multi-zones + répétition ── -->

          <div class="shl-field-group">
            <label>Zones à prospecter <span class="required">*</span></label>

            <?php if ( ! empty( $zones ) ) : ?>
            <div class="shl-zone-checklist" id="shl-zone-checklist">
              <?php foreach ( $zones as $z ) : ?>
              <label class="shl-zone-check-item">
                <input type="checkbox" name="zone_ids[]"
                       value="<?php echo esc_attr( $z->id ); ?>"
                       class="shl-zone-checkbox"
                       data-name="<?php echo esc_attr( $z->name ); ?>"
                       data-commune="<?php echo esc_attr( $z->commune ); ?>">
                <span class="shl-zone-check-label">
                  <strong><?php echo esc_html( $z->name ); ?></strong>
                  <small> — <?php echo esc_html( $z->commune ); ?></small>
                </span>
              </label>
              <?php endforeach; ?>
            </div>
            <div class="shl-zone-free-toggle">
              <button type="button" class="shl-btn-link" id="shl-toggle-free">+ Zone non répertoriée dans la liste</button>
            </div>
            <?php endif; ?>

            <div id="shl-free-zone-box" class="shl-free-zone-box"<?php echo ! empty( $zones ) ? ' style="display:none"' : ''; ?>>
              <?php if ( ! empty( $zones ) ) : ?>
              <p>Saisissez une zone qui n'est pas dans la liste ci-dessus :</p>
              <?php endif; ?>
              <div class="shl-field-row">
                <div class="shl-field-group">
                  <label for="zone_name">Plage / Secteur</label>
                  <input type="text" name="zone_name" id="zone_name" placeholder="ex : Plage de la Tamarissière">
                </div>
                <div class="shl-field-group">
                  <label for="commune">Commune</label>
                  <input type="text" name="commune" id="commune" placeholder="ex : Agde">
                </div>
              </div>
              <div class="shl-field-row">
                <div class="shl-field-group">
                  <label for="latitude">Latitude GPS</label>
                  <input type="text" name="latitude" id="latitude" placeholder="43.2965" pattern="[-0-9.]+">
                </div>
                <div class="shl-field-group">
                  <label for="longitude">Longitude GPS</label>
                  <input type="text" name="longitude" id="longitude" placeholder="3.4752" pattern="[-0-9.]+">
                </div>
              </div>
            </div>
          </div>

          <div class="shl-field-group">
            <label for="meeting_point">Point de rendez-vous (commun à toutes les zones)</label>
            <input type="text" name="meeting_point" id="meeting_point" placeholder="ex : Parking du Grau d'Agde, entrée Est">
          </div>

          <!-- Mode de création -->
          <div class="shl-field-group">
            <label>Mode de création</label>
            <div class="shl-mode-radio-group">
              <label class="shl-mode-radio active" id="shl-label-single">
                <input type="radio" name="repeat_mode" value="single" id="shl-mode-single" checked>
                📅 Date unique
              </label>
              <label class="shl-mode-radio" id="shl-label-weekly">
                <input type="radio" name="repeat_mode" value="weekly" id="shl-mode-weekly">
                🔄 Répétition hebdomadaire
              </label>
            </div>
          </div>

          <!-- Section date unique -->
          <div id="shl-single-date-section">
            <div class="shl-field-group">
              <label for="date">Date <span class="required">*</span></label>
              <input type="date" name="date" id="date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
            </div>
          </div>

          <!-- Section répétition -->
          <div id="shl-weekly-section" style="display:none">
            <div class="shl-repeat-box">
              <div class="shl-field-row">
                <div class="shl-field-group">
                  <label for="shl-date-from">Du <span class="required">*</span></label>
                  <input type="date" name="date_from" id="shl-date-from">
                </div>
                <div class="shl-field-group">
                  <label for="shl-date-to">Au <span class="required">*</span></label>
                  <input type="date" name="date_to" id="shl-date-to">
                </div>
              </div>
              <div class="shl-field-group">
                <label>Jours de la semaine <span class="required">*</span></label>
                <div class="shl-weekday-grid">
                  <?php
                  $weekdays_fr = array( 1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 0 => 'Dim' );
                  foreach ( $weekdays_fr as $dow => $lbl ) : ?>
                  <label class="shl-weekday-btn">
                    <input type="checkbox" name="weekdays[]" value="<?php echo esc_attr( $dow ); ?>" class="shl-weekday-cb">
                    <span class="shl-wd-label"><?php echo esc_html( $lbl ); ?></span>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div id="shl-slot-preview" class="shl-slot-preview" style="display:none">
                ✅ <strong><span id="shl-slot-count">0</span> créneau(x)</strong> seront créés
              </div>
            </div>
          </div>

          <!-- Horaires (toujours affichés pour les deux modes) -->
          <div class="shl-field-row" style="margin-top:14px">
            <div class="shl-field-group">
              <label for="time_start">Heure départ <span class="required">*</span></label>
              <input type="time" name="time_start" id="time_start" value="<?php echo esc_attr( $v['time_start'] ); ?>" required>
            </div>
            <div class="shl-field-group">
              <label for="time_end">Heure fin (optionnel)</label>
              <input type="time" name="time_end" id="time_end" value="">
            </div>
          </div>

          <?php endif; ?>
        </div>

        <!-- Consignes -->
        <div class="shl-card">
          <h2 class="shl-card-title">📋 Consignes & référent</h2>
          <div class="shl-field-group">
            <label for="referent">Référent du créneau</label>
            <input type="text" name="referent" id="referent" value="<?php echo esc_attr( $v['referent'] ); ?>" placeholder="Prénom Nom ou pseudonyme">
          </div>
          <div class="shl-field-group">
            <label for="instructions">Consignes spécifiques à ce créneau</label>
            <textarea name="instructions" id="instructions" rows="5" placeholder="Précisions particulières pour ce créneau..."><?php echo esc_textarea( $v['instructions'] ); ?></textarea>
            <p class="shl-help">Si vide, les consignes générales définies dans les Réglages s'appliqueront.</p>
          </div>
        </div>
      </div>

      <!-- Colonne droite -->
      <div class="shl-form-col">
        <div class="shl-card">
          <h2 class="shl-card-title">⚙️ Paramètres</h2>

          <div class="shl-field-group">
            <label>Type de prospection <span class="required">*</span></label>
            <div class="shl-radio-group">
              <label class="shl-radio-label">
                <input type="radio" name="type_prospect" value="foot" <?php checked( $v['type_prospect'], 'foot' ); ?>>
                🚶 À pied
              </label>
              <label class="shl-radio-label">
                <input type="radio" name="type_prospect" value="drone" <?php checked( $v['type_prospect'], 'drone' ); ?>>
                🚁 Drone
              </label>
              <label class="shl-radio-label">
                <input type="radio" name="type_prospect" value="mixed" <?php checked( $v['type_prospect'], 'mixed' ); ?>>
                🔀 Mixte
              </label>
            </div>
          </div>

          <div class="shl-field-row">
            <div class="shl-field-group">
              <label for="places_total">Nombre de places <span class="required">*</span></label>
              <input type="number" name="places_total" id="places_total" value="<?php echo esc_attr( $v['places_total'] ); ?>" min="1" max="50" required>
            </div>
            <div class="shl-field-group">
              <label for="status">Statut</label>
              <select name="status" id="status">
                <option value="open"      <?php selected( $v['status'], 'open' ); ?>>Ouvert</option>
                <option value="full"      <?php selected( $v['status'], 'full' ); ?>>Complet</option>
                <option value="cancelled" <?php selected( $v['status'], 'cancelled' ); ?>>Annulé</option>
                <option value="done"      <?php selected( $v['status'], 'done' ); ?>>Réalisé</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Résultats -->
        <div class="shl-card">
          <h2 class="shl-card-title">🔍 Résultat de la prospection</h2>
          <div class="shl-field-group">
            <label>Résultat</label>
            <div class="shl-radio-group">
              <label class="shl-radio-label"><input type="radio" name="result" value=""          <?php checked( $v['result'], '' ); ?>> — Non renseigné</label>
              <label class="shl-radio-label"><input type="radio" name="result" value="none"      <?php checked( $v['result'], 'none' ); ?>> Aucune trace</label>
              <label class="shl-radio-label"><input type="radio" name="result" value="suspect"   <?php checked( $v['result'], 'suspect' ); ?>> ⚠ Trace suspecte</label>
              <label class="shl-radio-label"><input type="radio" name="result" value="confirmed" <?php checked( $v['result'], 'confirmed' ); ?>> ✅ Trace confirmée</label>
              <label class="shl-radio-label"><input type="radio" name="result" value="other"     <?php checked( $v['result'], 'other' ); ?>> 👁 Autre observation</label>
            </div>
          </div>
          <div class="shl-field-group">
            <label for="result_comment">Commentaire terrain</label>
            <textarea name="result_comment" id="result_comment" rows="4" placeholder="Notes sur la prospection réalisée..."><?php echo esc_textarea( $v['result_comment'] ); ?></textarea>
          </div>
        </div>

        <!-- Inscrits (édition seulement) -->
        <?php if ( $is_edit ) : ?>
        <div class="shl-card">
          <h2 class="shl-card-title">👥 Inscrits (<?php echo esc_html( $slot->places_taken ); ?>/<?php echo esc_html( $slot->places_total ); ?>)</h2>
          <?php $regs = SHL_Tortues_DB::get_slot_registrations( $slot->id ); ?>
          <?php if ( empty( $regs ) ) : ?>
            <p class="shl-empty">Aucun inscrit pour ce créneau.</p>
          <?php else : ?>
            <ul class="shl-registrants-list">
              <?php foreach ( $regs as $r ) : ?>
                <li>
                  <strong><?php echo esc_html( $r->firstname . ' ' . $r->lastname ); ?></strong>
                  <span class="shl-reg-email"><?php echo esc_html( $r->email ); ?></span>
                  <?php if ( $r->phone ) : ?><span class="shl-reg-phone"><?php echo esc_html( $r->phone ); ?></span><?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="shl-btn shl-btn-outline shl-copy-btn" data-slot="<?php echo esc_attr( $slot->id ); ?>">📋 Copier la liste</button>
          <?php endif; ?>
          <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot->id ) ); ?>" class="shl-link">Gérer les inscriptions →</a></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="shl-form-actions">
      <button type="submit" class="shl-btn shl-btn-primary shl-btn-lg" id="shl-submit-btn">
        <?php echo $is_edit ? '💾 Enregistrer les modifications' : '➕ Créer le créneau'; ?>
      </button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=shl-tortues-slots' ) ); ?>" class="shl-btn shl-btn-ghost">Annuler</a>
    </div>

  </form>
</div>

<script>
<?php if ( $is_edit ) : ?>
function shlFillZone(sel) {
  var opt = sel.options[sel.selectedIndex];
  document.getElementById('shl-zone-id').value = opt.value;
  if (opt.value) {
    document.getElementById('zone_name').value = opt.dataset.name    || '';
    document.getElementById('commune').value   = opt.dataset.commune || '';
    document.getElementById('latitude').value  = opt.dataset.lat     || '';
    document.getElementById('longitude').value = opt.dataset.lng     || '';
  }
}
<?php else : ?>
(function(){
  var freeBox    = document.getElementById('shl-free-zone-box');
  var toggleBtn  = document.getElementById('shl-toggle-free');
  var modeSingle = document.getElementById('shl-mode-single');
  var modeWeekly = document.getElementById('shl-mode-weekly');
  var secSingle  = document.getElementById('shl-single-date-section');
  var secWeekly  = document.getElementById('shl-weekly-section');
  var lblSingle  = document.getElementById('shl-label-single');
  var lblWeekly  = document.getElementById('shl-label-weekly');
  var submitBtn  = document.getElementById('shl-submit-btn');

  // Toggle zone libre
  if (toggleBtn && freeBox) {
    toggleBtn.addEventListener('click', function(){
      var hidden = freeBox.style.display === 'none';
      freeBox.style.display = hidden ? 'block' : 'none';
      this.textContent = hidden ? '− Masquer la zone libre' : '+ Zone non répertoriée dans la liste';
      updatePreview();
    });
  }

  // Basculement mode
  function setMode(mode) {
    var weekly = mode === 'weekly';
    secSingle.style.display = weekly ? 'none' : 'block';
    secWeekly.style.display = weekly ? 'block' : 'none';
    lblSingle.classList.toggle('active', !weekly);
    lblWeekly.classList.toggle('active',  weekly);
    submitBtn.textContent = weekly ? '➕ Créer tous les créneaux' : '➕ Créer le créneau';
    updatePreview();
  }
  modeSingle.addEventListener('change', function(){ setMode('single'); });
  modeWeekly.addEventListener('change', function(){ setMode('weekly'); });

  // Compte les zones sélectionnées
  function countZones() {
    var n = document.querySelectorAll('.shl-zone-checkbox:checked').length;
    if (n > 0) return n;
    if (freeBox && freeBox.style.display !== 'none') {
      var el = document.getElementById('zone_name');
      if (el && el.value.trim()) return 1;
    }
    return 1;
  }

  // Mise à jour du compteur
  function updatePreview() {
    if (!modeWeekly.checked) return;
    var from    = document.getElementById('shl-date-from').value;
    var to      = document.getElementById('shl-date-to').value;
    var preview = document.getElementById('shl-slot-preview');
    var cntEl   = document.getElementById('shl-slot-count');
    if (!from || !to) { preview.style.display = 'none'; return; }
    var days = [];
    document.querySelectorAll('.shl-weekday-cb:checked').forEach(function(cb){ days.push(parseInt(cb.value)); });
    if (!days.length) { preview.style.display = 'none'; return; }
    var d = new Date(from + 'T00:00:00'), end = new Date(to + 'T00:00:00');
    if (d > end) { preview.style.display = 'none'; return; }
    var count = 0;
    while (d <= end) { if (days.indexOf(d.getDay()) !== -1) count++; d.setDate(d.getDate() + 1); }
    var total = count * countZones();
    cntEl.textContent = total;
    preview.style.display = total > 0 ? 'block' : 'none';
  }

  document.getElementById('shl-date-from').addEventListener('change', updatePreview);
  document.getElementById('shl-date-to').addEventListener('change',   updatePreview);
  document.querySelectorAll('.shl-weekday-cb').forEach(function(cb){ cb.addEventListener('change', updatePreview); });
  document.querySelectorAll('.shl-zone-checkbox').forEach(function(cb){ cb.addEventListener('change', updatePreview); });

  // Validation avant soumission
  document.getElementById('shl-slot-form').addEventListener('submit', function(e){
    var hasZoneList = !!document.getElementById('shl-zone-checklist');
    var zoneChecked = document.querySelectorAll('.shl-zone-checkbox:checked').length;
    var freeVisible = freeBox && freeBox.style.display !== 'none';
    var freeName    = document.getElementById('zone_name') ? document.getElementById('zone_name').value.trim() : '';

    if (hasZoneList && zoneChecked === 0 && (!freeVisible || !freeName)) {
      e.preventDefault();
      alert('Veuillez sélectionner au moins une zone ou saisir une zone libre.');
      return;
    }
    if (modeWeekly.checked) {
      var from = document.getElementById('shl-date-from').value;
      var to   = document.getElementById('shl-date-to').value;
      var days = document.querySelectorAll('.shl-weekday-cb:checked').length;
      if (!from || !to || !days) {
        e.preventDefault();
        alert('Mode répétition : renseignez la date de début, la date de fin et au moins un jour de la semaine.');
        return;
      }
    }
  });
})();
<?php endif; ?>
</script>
