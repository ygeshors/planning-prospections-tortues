<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap shl-wrap">

  <div class="shl-page-header">
    <span class="shl-page-icon">⚙️</span>
    <div>
      <h1>Réglages</h1>
      <p class="shl-subtitle">Configuration du Planning Prospections Tortues Marines</p>
    </div>
  </div>

  <?php if ( $saved ) : ?>
    <div class="notice notice-success is-dismissible"><p>Réglages enregistrés avec succès.</p></div>
  <?php endif; ?>

  <form method="post" action="" class="shl-form">
    <?php wp_nonce_field( 'shl_settings_save' ); ?>

    <div class="shl-settings-grid">

      <!-- Général -->
      <div class="shl-card">
        <h2 class="shl-card-title">📧 Général</h2>

        <div class="shl-field-group">
          <label for="admin_email">Email administrateur</label>
          <input type="email" name="admin_email" id="admin_email"
                 value="<?php echo esc_attr( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ); ?>"
                 required placeholder="admin@association.fr">
          <p class="shl-help">Les notifications de nouvelles inscriptions seront envoyées à cette adresse.</p>
        </div>

        <div class="shl-field-group">
          <label for="benevole_url">URL de l'espace bénévole</label>
          <input type="url" name="benevole_url" id="benevole_url"
                 value="<?php echo esc_attr( get_option( 'shl_tortues_benevole_url', '' ) ); ?>"
                 placeholder="https://votre-site.fr/espace-benevole/">
          <p class="shl-help">URL de la page qui contient le shortcode <code>[espace_benevole]</code>. Ce lien sera inclus dans tous les emails envoyés aux bénévoles.</p>
        </div>

        <div class="shl-field-group">
          <label>
            <input type="checkbox" name="show_names" value="1" <?php checked( get_option( 'shl_tortues_show_names', '1' ), '1' ); ?>>
            Afficher les prénoms des inscrits sur la vue publique
          </label>
          <p class="shl-help">Si coché, les prénoms (initiale du nom) des bénévoles inscrits seront visibles publiquement sur chaque créneau.</p>
        </div>

        <div class="shl-field-row">
          <div class="shl-field-group">
            <label for="default_places">Nombre de places par défaut</label>
            <input type="number" name="default_places" id="default_places"
                   value="<?php echo esc_attr( get_option( 'shl_tortues_default_places', 2 ) ); ?>" min="1" max="50">
          </div>
          <div class="shl-field-group">
            <label for="primary_color">Couleur principale</label>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="color" name="primary_color" id="primary_color"
                     value="<?php echo esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) ); ?>"
                     style="width:50px;height:36px;cursor:pointer;border-radius:4px;border:1px solid #ddd">
              <input type="text" id="primary_color_text"
                     value="<?php echo esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) ); ?>"
                     style="width:100px" pattern="#[0-9A-Fa-f]{6}"
                     oninput="document.getElementById('primary_color').value=this.value">
            </div>
            <p class="shl-help">Couleur utilisée dans les boutons et les emails.</p>
          </div>
        </div>
      </div>

      <!-- Message de confirmation -->
      <div class="shl-card">
        <h2 class="shl-card-title">📨 Message de confirmation</h2>
        <p class="shl-help" style="margin-bottom:12px">Variables disponibles : <code>{prenom}</code> <code>{nom}</code> <code>{date}</code> <code>{plage}</code> <code>{commune}</code> <code>{heure}</code> <code>{rdv}</code> <code>{type}</code> <code>{consignes}</code></p>
        <div class="shl-field-group">
          <textarea name="confirm_message" rows="12" style="font-family:monospace;font-size:13px"><?php echo esc_textarea( get_option( 'shl_tortues_confirm_message', '' ) ); ?></textarea>
        </div>
      </div>

      <!-- Consignes générales -->
      <div class="shl-card">
        <h2 class="shl-card-title">📋 Consignes générales</h2>
        <p class="shl-help" style="margin-bottom:12px">Ces consignes s'affichent sur la vue publique des créneaux qui n'ont pas de consignes spécifiques.</p>
        <div class="shl-field-group">
          <textarea name="general_instructions" rows="8"><?php echo esc_textarea( get_option( 'shl_tortues_general_instructions', '' ) ); ?></textarea>
        </div>
      </div>

      <!-- Alertes météo -->
      <div class="shl-card">
        <h2 class="shl-card-title">🌩️ Alertes météo automatiques</h2>
        <p class="shl-help" style="margin-bottom:16px">Le plugin vérifie chaque matin la météo des créneaux à venir et envoie une alerte si les conditions sont dangereuses. Les créneaux doivent avoir des coordonnées GPS renseignées.</p>

        <div class="shl-field-row">
          <div class="shl-field-group">
            <label for="weather_alert_days">Anticipation (jours)</label>
            <input type="number" name="weather_alert_days" id="weather_alert_days" min="1" max="7"
                   value="<?php echo esc_attr( get_option( 'shl_tortues_weather_alert_days', 2 ) ); ?>">
            <p class="shl-help">Créneaux dans les N prochains jours.</p>
          </div>
          <div class="shl-field-group">
            <label for="weather_alert_wind">Seuil vent (km/h)</label>
            <input type="number" name="weather_alert_wind" id="weather_alert_wind" min="20" max="150"
                   value="<?php echo esc_attr( get_option( 'shl_tortues_weather_alert_wind', 50 ) ); ?>">
            <p class="shl-help">Alerte si vent ≥ seuil. Les orages déclenchent toujours une alerte.</p>
          </div>
          <div class="shl-field-group">
            <label for="weather_alert_rain">Seuil pluie (mm)</label>
            <input type="number" name="weather_alert_rain" id="weather_alert_rain" min="5" max="100"
                   value="<?php echo esc_attr( get_option( 'shl_tortues_weather_alert_rain', 20 ) ); ?>">
            <p class="shl-help">Alerte si précipitations journalières ≥ seuil.</p>
          </div>
        </div>
      </div>

      <!-- Désinstallation -->
      <div class="shl-card shl-card-danger">
        <h2 class="shl-card-title">🗑️ Désinstallation</h2>
        <div class="shl-field-group">
          <label>
            <input type="checkbox" name="allow_uninstall_cleanup" value="1"
                   <?php checked( get_option( 'shl_tortues_allow_uninstall_cleanup', '0' ), '1' ); ?>>
            Supprimer toutes les données lors de la désinstallation du plugin
          </label>
          <p class="shl-help shl-help-danger">⚠ Si coché, toutes les tables et options seront supprimées définitivement lors de la désinstallation. Laissez décoché pour conserver vos données.</p>
        </div>
      </div>
    </div>

    <div class="shl-form-actions">
      <button type="submit" name="shl_save_settings" class="shl-btn shl-btn-primary shl-btn-lg">💾 Enregistrer les réglages</button>
    </div>
  </form>

  <!-- Shortcode + info version -->
  <div class="shl-card shl-card-info" style="margin-top:24px">
    <h3>ℹ️ Informations</h3>
    <table class="shl-info-table">
      <tr><td>Version du plugin</td><td><?php echo esc_html( SHL_TORTUES_VERSION ); ?></td></tr>
      <tr><td>Shortcode public</td><td><code>[planning_tortues]</code></td></tr>
      <tr><td>Version DB</td><td><?php echo esc_html( get_option( 'shl_tortues_db_version', '—' ) ); ?></td></tr>
    </table>
  </div>

</div>
<script>
document.getElementById('primary_color').addEventListener('input', function() {
  document.getElementById('primary_color_text').value = this.value;
});
</script>
