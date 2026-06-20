<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attestation de bénévolat – <?php echo esc_html( $volunteer_name ); ?> – <?php echo esc_html( $year ); ?></title>
  <style>
    @page { size: A4 portrait; margin: 18mm 16mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #222; background: #fff; }

    /* En-tête */
    .att-header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 3px solid #2E86AB; padding-bottom: 14px; margin-bottom: 18px; }
    .att-logo-area { display: flex; align-items: center; gap: 12px; }
    .att-logo-img { height: 55px; width: auto; }
    .att-org-name { font-size: 14px; font-weight: 700; color: #1a5f7a; line-height: 1.3; }
    .att-org-sub  { font-size: 11px; color: #666; margin-top: 3px; }
    .att-doc-ref  { text-align: right; font-size: 10px; color: #888; }

    /* Titre */
    .att-title-block { text-align: center; margin-bottom: 20px; }
    .att-title { font-size: 20px; font-weight: 800; color: #1a5f7a; text-transform: uppercase; letter-spacing: 1px; }
    .att-subtitle { font-size: 13px; color: #555; margin-top: 4px; }

    /* Section bénévole */
    .att-section { margin-bottom: 16px; }
    .att-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #2E86AB; border-bottom: 1px solid #d0e8f0; padding-bottom: 4px; margin-bottom: 10px; }
    .att-info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 4px 0; }
    .att-info-label { font-weight: 600; color: #555; padding: 3px 0; }
    .att-info-value { padding: 3px 0; color: #222; }

    /* Stats */
    .att-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
    .att-stat-box { border: 1px solid #d0e8f0; border-radius: 8px; padding: 10px; text-align: center; background: #f4fbff; }
    .att-stat-num { font-size: 22px; font-weight: 800; color: #1a5f7a; }
    .att-stat-lbl { font-size: 10px; color: #666; margin-top: 2px; }

    /* Table prospections */
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
    thead th { background: #1a5f7a; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
    tbody tr:nth-child(even) { background: #f4fbff; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #e5f0f8; }
    .att-badge { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: 600; }
    .att-badge-done { background: #d4edda; color: #1a5f3a; }
    .att-badge-foot { background: #cce5f4; color: #1a5f7a; }
    .att-badge-drone { background: #fce5b8; color: #7a4f0a; }

    /* Attestation text */
    .att-certif-box { border: 2px solid #2E86AB; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; background: #f4fbff; font-size: 12px; line-height: 1.7; }
    .att-certif-box strong { color: #1a5f7a; }

    /* Signature */
    .att-signature { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px; }
    .att-sig-block { text-align: center; width: 220px; }
    .att-sig-line { border-top: 1px solid #555; margin-top: 40px; padding-top: 6px; font-size: 10px; color: #666; }

    /* Footer */
    .att-footer { border-top: 1px solid #e0e0e0; margin-top: 20px; padding-top: 8px; text-align: center; font-size: 9px; color: #aaa; }

    /* Print */
    @media print {
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<!-- Bouton impression (masqué à l'impression) -->
<div class="no-print" style="background:#1a5f7a;padding:10px 20px;text-align:center;position:sticky;top:0;z-index:10">
  <button onclick="window.print()" style="background:#fff;color:#1a5f7a;font-weight:700;font-size:13px;border:none;padding:8px 24px;border-radius:6px;cursor:pointer">🖨️ Imprimer / Enregistrer en PDF</button>
  <span style="color:rgba(255,255,255,.7);font-size:12px;margin-left:16px">Utilisez Ctrl+P ou le bouton ci-dessus</span>
</div>

<div style="padding: 20px 30px; max-width: 820px; margin: 0 auto;">

  <!-- En-tête -->
  <div class="att-header">
    <div class="att-logo-area">
      <img src="https://ashl.fr/wp-content/uploads/2025/03/Copie-de-Copie-de-Sans-titre-297-x-210-mm-6-300x212.png" alt="Sauvegarde Hérault Littoral" class="att-logo-img">
      <div>
        <div class="att-org-name">Sauvegarde Hérault Littoral</div>
        <div class="att-org-sub">Association de protection de la nature – 1 rue Kléber, 34410 Sérignan<br>
        Programme de suivi des traces de tortues marines en Méditerranée (RTMMF)</div>
      </div>
    </div>
    <div class="att-doc-ref">
      <div>Document généré le <?php echo esc_html( date_i18n( 'd/m/Y' ) ); ?></div>
      <div style="margin-top:4px">Saison <?php echo esc_html( $year ); ?></div>
    </div>
  </div>

  <!-- Titre -->
  <div class="att-title-block">
    <div class="att-title">Attestation de bénévolat</div>
    <div class="att-subtitle">Prospections de traces de tortues marines – Plages de l'Hérault</div>
  </div>

  <!-- Texte d'attestation -->
  <div class="att-certif-box">
    <p>L'association <strong>Sauvegarde Hérault Littoral (SHL)</strong>, opérateur du programme de suivi des traces de tortues marines RTMMF en Méditerranée, atteste que :</p>
    <p style="margin-top:10px;font-size:14px;font-weight:700;color:#1a5f7a;text-align:center">
      <?php echo esc_html( strtoupper( $volunteer_name ) ); ?>
    </p>
    <?php if ( ! empty( $first->email ) ) : ?>
    <p style="text-align:center;font-size:11px;color:#666;margin-top:2px"><?php echo esc_html( $first->email ); ?></p>
    <?php endif; ?>
    <p style="margin-top:10px">
      a participé en tant que <strong>bénévole</strong> aux prospections de traces de tortues marines
      sur les plages de l'Hérault au cours de la saison <strong><?php echo esc_html( $year ); ?></strong>,
      dans le cadre du programme national de suivi des tortues marines en Méditerranée.
    </p>
  </div>

  <!-- Infos bénévole -->
  <div class="att-section">
    <div class="att-section-title">Informations du bénévole</div>
    <div class="att-info-grid">
      <span class="att-info-label">Nom complet</span>
      <span class="att-info-value"><?php echo esc_html( $volunteer_name ); ?></span>
      <span class="att-info-label">Email</span>
      <span class="att-info-value"><?php echo esc_html( $first->email ); ?></span>
      <?php if ( $first->phone ) : ?>
      <span class="att-info-label">Téléphone</span>
      <span class="att-info-value"><?php echo esc_html( $first->phone ); ?></span>
      <?php endif; ?>
      <span class="att-info-label">Adhérent(e) SHL</span>
      <span class="att-info-value"><?php echo $first->is_member ? 'Oui' : 'Non'; ?></span>
    </div>
  </div>

  <!-- Statistiques saison -->
  <div class="att-section">
    <div class="att-section-title">Bilan de la saison <?php echo esc_html( $year ); ?></div>
    <div class="att-stats-row">
      <div class="att-stat-box">
        <div class="att-stat-num"><?php echo count( $season_history ); ?></div>
        <div class="att-stat-lbl">Prospections<br>réalisées</div>
      </div>
      <div class="att-stat-box">
        <div class="att-stat-num"><?php echo count( $season_history ) > 0 ? esc_html( $heures ) . 'h' : '—'; ?></div>
        <div class="att-stat-lbl">Heures<br>bénévoles</div>
      </div>
      <div class="att-stat-box">
        <div class="att-stat-num"><?php echo esc_html( $nb_obs ); ?></div>
        <div class="att-stat-lbl">Observations<br>soumises</div>
      </div>
      <div class="att-stat-box">
        <?php
        $plages = array_unique( array_column( $season_history, 'zone_name' ) );
        ?>
        <div class="att-stat-num"><?php echo count( $plages ); ?></div>
        <div class="att-stat-lbl">Plage(s)<br>différente(s)</div>
      </div>
    </div>
  </div>

  <!-- Table des prospections -->
  <?php if ( ! empty( $season_history ) ) : ?>
  <div class="att-section">
    <div class="att-section-title">Détail des participations</div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Plage</th>
          <th>Commune</th>
          <th>Type</th>
          <th>Horaires</th>
          <th>Observations</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $type_labels = array( 'foot' => 'À pied', 'drone' => 'Drone', 'mixed' => 'Mixte' );
        foreach ( $season_history as $h ) :
        ?>
        <tr>
          <td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->date ) ) ); ?></td>
          <td><?php echo esc_html( $h->zone_name ); ?></td>
          <td><?php echo esc_html( $h->commune ); ?></td>
          <td>
            <?php
            $t = $h->type_prospect;
            $cls = 'foot' === $t ? 'att-badge-foot' : ( 'drone' === $t ? 'att-badge-drone' : '' );
            echo '<span class="att-badge ' . esc_attr( $cls ) . '">' . esc_html( $type_labels[ $t ] ?? $t ) . '</span>';
            ?>
          </td>
          <td>
            <?php
            if ( $h->actual_time_start ) {
                echo esc_html( substr( $h->actual_time_start, 0, 5 ) );
                if ( $h->actual_time_end ) {
                    echo ' → ' . esc_html( substr( $h->actual_time_end, 0, 5 ) );
                }
            } else {
                echo esc_html( substr( $h->time_start, 0, 5 ) );
            }
            ?>
          </td>
          <td><?php echo esc_html( $h->obs_count ); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else : ?>
  <p style="color:#888;font-size:12px;font-style:italic;margin-bottom:16px">
    Aucune prospection réalisée et enregistrée pour cette saison.
  </p>
  <?php endif; ?>

  <!-- Signature -->
  <div class="att-signature">
    <div class="att-sig-block">
      <div class="att-sig-line">Fait à Sérignan, le <?php echo esc_html( date_i18n( 'd F Y' ) ); ?><br>Pour l'association SHL</div>
    </div>
    <div style="text-align:center;font-size:11px;color:#888">
      <img src="https://ashl.fr/wp-content/uploads/2025/03/Copie-de-Copie-de-Sans-titre-297-x-210-mm-6-300x212.png" alt="SHL" style="height:45px;width:auto;margin-bottom:6px;display:block;margin:0 auto 6px">
      <div style="font-weight:700;color:#1a5f7a">Sauvegarde Hérault Littoral</div>
      <div>1 rue Kléber – 34410 Sérignan</div>
      <div>contact@sauvegarde-herault-littoral.fr</div>
      <div>04 23 50 03 38</div>
    </div>
    <div class="att-sig-block">
      <div class="att-sig-line">Signature du/de la bénévole<br>&nbsp;</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="att-footer">
    Ce document est généré automatiquement par le système de gestion des prospections de l'association Sauvegarde Hérault Littoral (SHL).
    Participant au Réseau Tortues Marines Méditerranée Française (RTMMF).
    Document non commercial. Reproduction autorisée pour usage personnel.
  </div>

</div>

</body>
</html>
