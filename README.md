![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![REST](https://img.shields.io/badge/REST-API-009688?style=for-the-badge&logo=fastapi&logoColor=white)
 
# PME Backend — API RESTful Laravel 11
 
**Plateforme de gestion organisationnelle du Parti du Maroc Émergent (PME / حزب المغرب الصاعد)**
 
Développé par **WANDICH Achraf** · Stage de fin d'études @ **Mediatower** · 2025–2026  
Encadrants : **F. GMIRA & R. QESMI** · Directeur : **M. Hafid El Maktoub**  
Client : **M. Ali Amzine** — Parti du Maroc Émergent
 
---
 
## Contexte du projet
 
Ce projet a été réalisé dans le cadre d'un stage de fin d'études au sein de la société **Mediatower**, entreprise marocaine fondée en 2023, spécialisée dans les solutions numériques et l'intelligence artificielle, dirigée par **M. Hafid El Maktoub**.
 
Le projet a été commandité par **M. Ali Amzine**, fondateur et président du **Parti du Maroc Émergent (PME / حزب المغرب الصاعد)** — mouvement politique innovant prônant l'intelligence collective, une approche anti-populiste et une vision stratégique réaliste pour le développement du Maroc.
 
### Problématique
 
Avant ce projet, la gestion des activités du PME reposait sur des outils dispersés : fichiers Excel pour les membres, réseaux sociaux pour la communication, appels téléphoniques pour les événements. Cela entraînait :
 
- Absence d'un annuaire centralisé des membres, sympathisants et bénévoles
- Manque de traçabilité des activités, événements et rapports
- Absence de visibilité hiérarchique entre niveaux national, régional et local
- Aucun mécanisme structuré pour les donations et les adhésions
### Objectifs
 
- Gestion centralisée des utilisateurs selon une hiérarchie multi-niveaux
- Création, publication et suivi des événements à l'échelle nationale / régionale / locale
- Cycle de vie complet des rapports d'activité (pending → validé / refusé → archivé)
- Gestion des médias, sondages, donations et adhésions
- Journal d'audit automatique pour la traçabilité complète
- Tableau de bord statistique adapté à chaque niveau hiérarchique
---
 
## Stack technique
 
| Technologie | Version | Rôle |
| --- | --- | --- |
| PHP | 8.2 | Langage serveur |
| Laravel | 11 | Framework API-MVC |
| Laravel Sanctum | — | Personal Access Tokens |
| Eloquent ORM | — | Mapping objet-relationnel |
| MySQL | — | Base de données relationnelle |
| Composer | — | Gestionnaire de dépendances PHP |
 
---
 
## Prérequis
 
- PHP >= 8.2
- Composer
- MySQL
- Extensions PHP : `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `bcmath`
---
 
## Installation
 
```bash
# 1. Cloner le dépôt
git clone https://github.com/<votre-org>/pme_backend.git
cd pme_backend
 
# 2. Installer les dépendances
composer install
 
# 3. Copier le fichier d'environnement
cp .env.example .env
 
# 4. Générer la clé d'application
php artisan key:generate
 
# 5. Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pme_db
DB_USERNAME=root
DB_PASSWORD=
 
# 6. Exécuter les migrations
php artisan migrate
 
# 7. (Optionnel) Seeder les données initiales
php artisan db:seed
 
# 8. Lancer le serveur de développement
php artisan serve
```
 
L'API est accessible sur `http://127.0.0.1:8000/api`
 
---
 
## Structure du projet
 
```
pme_backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── EventController.php
│   │   │   ├── ReportController.php
│   │   │   ├── PollController.php
│   │   │   ├── DonationController.php
│   │   │   ├── MediaController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── StatsController.php
│   │   │   └── Concerns/
│   │   │       ├── ScopesByPartyBranch.php
│   │   │       └── RecordsAuditLogs.php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── Poll.php
│   │   ├── PartyBranch.php
│   │   ├── Report.php
│   │   ├── Donation.php
│   │   ├── Media.php
│   │   └── ...
│   └── Services/
│       ├── NotificationService.php
│       └── SimplePdfService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── config/
    ├── sanctum.php
    ├── cors.php
    └── ...
```
 
---
 
## Principaux endpoints API
 
| Méthode | Endpoint | Description | Auth |
| --- | --- | --- | --- |
| POST | `/api/login` | Authentification — obtention du token | Non |
| POST | `/api/logout` | Déconnexion — révocation du token | Oui |
| GET | `/api/user` | Profil de l'utilisateur connecté | Oui |
| GET / POST | `/api/events` | Liste / Création d'événements | Oui |
| POST | `/api/events/{id}/register` | Inscription à un événement | Oui |
| GET / POST | `/api/events/{id}/recaps` | Récapitulatifs post-événement | Oui |
| GET / POST | `/api/reports` | Rapports d'activité | Oui |
| GET / POST | `/api/news` | Actualités | Oui |
| GET / POST | `/api/polls` | Sondages | Oui |
| POST | `/api/polls/{id}/vote` | Vote unique par utilisateur | Oui |
| GET / POST | `/api/donations` | Donations | Oui |
| POST | `/api/membership-requests` | Demande d'adhésion | Non |
| GET | `/api/stats` | Statistiques tableau de bord | Oui |
| GET | `/api/audit-logs` | Journal d'audit — Super Admin uniquement | Oui |
| GET / POST | `/api/media` | Médias | Oui |
| GET / POST | `/api/users` | Gestion utilisateurs | Oui |
| GET / POST | `/api/branches` | Branches géographiques | Oui |
| GET | `/api/notifications` | Notifications internes | Oui |
 
---
 
## Authentification — Laravel Sanctum
 
Toutes les requêtes protégées doivent inclure l'en-tête :
 
```
Authorization: Bearer {token}
```
 
Réponse au login :
 
```json
{
  "token": "...",
  "user": { "id": 1, "name": "..." },
  "role": "admin",
  "branch": { "id": 1, "name": "..." }
}
```
 
Protection anti-brute-force : `throttle:6,1` — max 6 tentatives par minute.
 
---
 
## Hiérarchie des rôles
 
| Rôle | Périmètre |
| --- | --- |
| `admin` — Super Admin | Accès total, portée nationale |
| `regional_admin` | Filtré sur sa région, supervise les admins locaux |
| `local_admin` | Filtré sur sa structure locale |
| `member` | Accès personnel — événements, sondages, profil |
| `volunteer` | Participation aux événements |
 
Le middleware `CheckRole` et le trait `ScopesByPartyBranch` garantissent l'étanchéité entre niveaux.
 
---
 
## Codes de réponse HTTP
 
| Code | Signification |
| --- | --- |
| 200 | Succès |
| 201 | Ressource créée |
| 204 | Suppression / mise à jour sans contenu |
| 401 | Token absent ou invalide |
| 403 | Rôle insuffisant — CheckRole |
| 422 | Erreur de validation — Form Request |
| 429 | Rate limiting déclenché |
| 500 | Erreur interne non anticipée |
 
---
 
## Sécurité
 
- Sanctum `auth:sanctum` sur toutes les routes protégées
- bcrypt via `Hash::make()` — aucun mot de passe stocké en clair
- Rate limiting `throttle:6,1` contre le brute-force
- Form Requests — validation systématique avant tout traitement
- CORS configuré dans `config/cors.php`
- Soft delete sur `users` et `donations` — historique préservé
- `RecordsAuditLogs` — traçabilité automatique de toutes les actions sensibles
- Credentials dans `.env` — jamais dans le code source
---
 
## Base de données
 
29 tables MySQL — InnoDB / utf8mb4_unicode_ci (support trilingue FR / AR / EN)
 
| Catégorie | Tables |
| --- | --- |
| Utilisateurs | users, roles, party_branches |
| Événements | events, event_registrations, event_recaps |
| Contenu | news, media, static_pages |
| Démocratie | polls, poll_options, votes |
| Finances | donations, membership_requests |
| Suivi | reports, audit_logs, notifications |
| Communauté | sympathizers, volunteers, contacts, newsletter_subscribers |
| Laravel | migrations, personal_access_tokens, cache, jobs, sessions |
 
---
 
## Variables d'environnement
 
```env
APP_NAME=PME
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
 
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pme_db
DB_USERNAME=root
DB_PASSWORD=
 
SANCTUM_STATEFUL_DOMAINS=localhost:3000
FRONTEND_URL=http://localhost:3000
```
 
---
 
*Projet académique — Stage de fin d'études · WANDICH Achraf · Département Informatique, École Supérieure de Technologie · Client : M. Ali Amzine — Parti du Maroc Émergent · Entreprise d'accueil : Mediatower — M. Hafid El Maktoub · 2025–2026*
