# 🐢 Planning Prospections Tortues Marines

Plugin WordPress de gestion du planning de prospections pour la surveillance des tortues marines.  
Développé pour l'association **[Sauvegarde Hérault Littoral](https://ashl.fr)** — Hérault, France.

---

## Présentation

Ce plugin permet aux associations naturalistes de gérer des créneaux de prospection terrain, les inscriptions de bénévoles, les zones surveillées et les statistiques de saison — le tout intégré dans WordPress, sans dépendance externe lourde.

**Cas d'usage :** surveillance des nids de tortues marines sur le littoral méditerranéen, avec des bénévoles qui s'inscrivent en ligne à des créneaux hebdomadaires.

---

## Fonctionnalités

### Interface publique
- **Calendrier interactif** — vues mois / semaine / liste, navigation par swipe mobile
- **Modal d'inscription AJAX** — formulaire sans rechargement de page, carte de zone Leaflet intégrée
- **Carte des prospections** — shortcode `[carte_prospections]` avec polygones GeoJSON et marqueurs
- **Espace bénévole** — passeport numérique, attestation PDF, badges de participation
- **Mode sombre** — toggle 🌙/☀️ persisté en localStorage
- **Navigation mobile** — barre fixe en bas d'écran sur smartphone
- **Guide bénévole** — page HTML autonome `GUIDE_BENEVOLES.html` (hors WordPress)

### Interface admin
- **Tableau de bord** — stats temps réel, alertes météo OpenWeatherMap, créneaux à venir
- **Créneaux** — création unitaire ou répétition hebdomadaire sur une plage de dates
- **Inscriptions** — liste filtrée, validation/refus, liste d'attente automatique, broadcast email
- **Zones** — dessin GeoJSON, coordonnées GPS, commune, référent
- **Bénévoles** — profils, historique, badges
- **Rapport de saison** — statistiques annuelles exportables
- **Terrain/Tracks** — saisie et visualisation des observations GPS
- **Export CSV** — créneaux et inscriptions filtrés

### Emails
- Template HTML responsive (table-based) avec header gradient + vague SVG
- Emails : confirmation inscription, rappel J-1, annulation, liste d'attente, attestation

---

## Installation

1. Télécharger le ZIP depuis la page [Releases](../../releases)
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**
3. Activer le plugin — les tables BDD sont créées automatiquement
4. Configurer dans **Réglages → Tortues Marines** : couleur principale, clé OpenWeatherMap, URL espace bénévole, etc.
5. Insérer le shortcode `[planning_tortues]` dans une page

---

## Shortcodes

| Shortcode | Description |
|---|---|
| `[planning_tortues]` | Calendrier complet avec modal inscription |
| `[carte_prospections days="30"]` | Carte Leaflet des créneaux à venir |
| `[stats_tortues]` | Statistiques publiques de saison |
| `[espace_benevole]` | Portail bénévole (passeport, attestation) |
| `[terrain_tortues]` | Interface saisie terrain GPS |

---

## Stack technique

- **Back-end** : PHP 7.4+, WordPress (tables MySQL personnalisées, AJAX, cron)
- **Front-end** : Vanilla JS, CSS3 (variables, dark mode), police Nunito
- **Cartographie** : [Leaflet.js](https://leafletjs.com) + OpenStreetMap
- **Météo** : [OpenWeatherMap API](https://openweathermap.org/api)
- **Emails** : template HTML table-based, envoi via `wp_mail()`

---

## Structure du projet

```
planning-prospections-tortues/
├── admin/                    # Interface d'administration
│   ├── class-admin-*.php     # Contrôleurs admin
│   └── views/                # Vues PHP admin
├── assets/
│   ├── css/                  # admin.css · public.css · terrain.css
│   └── js/                   # admin.js · public.js · terrain.js
├── includes/                 # Classes core (DB, email, météo, export…)
├── public/                   # Interface publique (shortcodes, AJAX)
│   ├── class-*.php
│   └── views/
├── GUIDE_BENEVOLES.html      # Guide autonome pour les bénévoles
├── planning-prospections-tortues.php   # Point d'entrée du plugin
└── uninstall.php
```

---

## Configuration requise

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Clé API OpenWeatherMap (optionnelle, pour les alertes météo)

---

## Licence

Ce plugin est partagé librement pour usage associatif et éducatif.  
Toute réutilisation commerciale nécessite l'accord de l'auteur.

---

## Crédits

Développé pour l'association **Sauvegarde Hérault Littoral** dans le cadre du programme de surveillance des tortues marines sur le littoral de l'Hérault.

Conçu et développé avec [Claude Code](https://claude.ai/code) — Anthropic.
