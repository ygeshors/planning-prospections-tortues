<?php if ( ! defined( 'ABSPATH' ) ) exit;
$shl_benevole_url = esc_url( get_option( 'shl_tortues_benevole_url', '' ) );
$shl_guide_url    = esc_url( SHL_TORTUES_PLUGIN_URL . 'GUIDE_BENEVOLES.html' );
?>
<div id="shl-planning" class="shl-planning" role="main">

  <!-- En-tête calendrier -->
  <div class="shl-cal-header">
    <div class="shl-cal-brand">
      <span class="shl-cal-logo">🐢</span>
      <div>
        <h2 class="shl-cal-title">Planning Prospections Tortues Marines</h2>
        <p class="shl-cal-subtitle">Sauvegarde Hérault Littoral – Hérault, France</p>
      </div>
    </div>

    <div class="shl-header-controls">
      <!-- Onglets de vue -->
      <div class="shl-view-tabs" role="tablist">
        <button class="shl-tab shl-tab-active" data-view="month"  role="tab" aria-selected="true">📅 Mois</button>
        <button class="shl-tab"                data-view="week"   role="tab">📋 Semaine</button>
        <button class="shl-tab"                data-view="list"   role="tab">📜 Liste</button>
      </div>
      <!-- Toggle mode sombre -->
      <button class="shl-dark-toggle" id="shl-dark-toggle" aria-label="Basculer mode sombre" title="Mode sombre">🌙</button>
    </div>
  </div>

  <!-- Barre de navigation mois/semaine -->
  <div class="shl-cal-nav">
    <button class="shl-nav-btn" id="shl-prev" aria-label="Précédent">‹</button>
    <h3 class="shl-period-label" id="shl-period-label">Chargement…</h3>
    <button class="shl-nav-btn" id="shl-next" aria-label="Suivant">›</button>
    <button class="shl-nav-btn shl-today-btn" id="shl-today">Aujourd'hui</button>
  </div>

  <!-- Légende -->
  <div class="shl-legend">
    <span class="shl-legend-item"><span class="shl-dot shl-dot-foot"></span>À pied</span>
    <span class="shl-legend-item"><span class="shl-dot shl-dot-drone"></span>Drone</span>
    <span class="shl-legend-item"><span class="shl-dot shl-dot-mixed"></span>Mixte</span>
    <span class="shl-legend-item"><span class="shl-dot shl-dot-full"></span>Complet</span>
  </div>

  <!-- Zone de rendu du calendrier -->
  <div id="shl-cal-body" class="shl-cal-body">
    <div class="shl-loading"><span class="shl-spinner"></span> Chargement du planning…</div>
  </div>

  <!-- Modal détail / inscription -->
  <div id="shl-modal-overlay" class="shl-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="shl-modal-title" hidden>
    <div class="shl-modal" id="shl-modal">
      <button class="shl-modal-close" id="shl-modal-close" aria-label="Fermer">✕</button>
      <div id="shl-modal-content"><!-- rempli par JS --></div>
    </div>
  </div>

  <!-- Navigation mobile (barre fixe en bas) -->
  <nav class="shl-mobile-nav" id="shl-mobile-nav" aria-label="Navigation rapide">
    <button class="shl-mobile-nav-item shl-mobile-nav-active" id="shl-mobile-cal" type="button">
      <span class="shl-mobile-nav-icon">📅</span>
      <span class="shl-mobile-nav-label">Planning</span>
    </button>
    <?php if ( $shl_benevole_url ) : ?>
    <a class="shl-mobile-nav-item" href="<?php echo $shl_benevole_url; ?>">
      <span class="shl-mobile-nav-icon">👤</span>
      <span class="shl-mobile-nav-label">Mon espace</span>
    </a>
    <?php else : ?>
    <button class="shl-mobile-nav-item" type="button" disabled style="opacity:.35">
      <span class="shl-mobile-nav-icon">👤</span>
      <span class="shl-mobile-nav-label">Mon espace</span>
    </button>
    <?php endif; ?>
    <a class="shl-mobile-nav-item" href="https://chat.whatsapp.com/E6XzXU3n86MDmIHpzJ7XvR" target="_blank" rel="noopener">
      <span class="shl-mobile-nav-icon">💬</span>
      <span class="shl-mobile-nav-label">WhatsApp</span>
    </a>
    <a class="shl-mobile-nav-item" href="<?php echo $shl_guide_url; ?>" target="_blank" rel="noopener">
      <span class="shl-mobile-nav-icon">📖</span>
      <span class="shl-mobile-nav-label">Guide</span>
    </a>
  </nav>

</div>

<!-- Templates inline -->
<script type="text/template" id="shl-tpl-slot-detail">
  <div class="shl-modal-header shl-type-{{type}}">
    <div class="shl-modal-type-badge">{{type_label}}</div>
    <h2 id="shl-modal-title" class="shl-modal-title">{{zone_name}}</h2>
    <p class="shl-modal-commune">{{commune}}</p>
  </div>
  <div class="shl-modal-body">
    <div class="shl-slot-infos">
      <div class="shl-info-row"><span class="shl-info-icon">📅</span><span>{{date_formatted}}</span></div>
      <div class="shl-info-row"><span class="shl-info-icon">⏰</span><span>{{time_start}}{{time_end_html}}</span></div>
      {{meeting_point_html}}
      <div class="shl-info-row"><span class="shl-info-icon">👥</span><span class="shl-places-display {{places_class}}">{{places_left}} place(s) disponible(s) sur {{places_total}}</span></div>
      {{referent_html}}
    </div>
    {{instructions_html}}
    {{names_html}}
    {{map_html}}
    <div class="shl-status-block shl-status-{{status}}">
      <span class="shl-status-label">{{status_label}}</span>
    </div>
    {{register_html}}
  </div>
</script>
