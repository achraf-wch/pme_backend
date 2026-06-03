<img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"/>
<img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
<img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
<img src="https://img.shields.io/badge/Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"/>
<img src="https://img.shields.io/badge/REST-API-009688?style=for-the-badge&logo=fastapi&logoColor=white"/>
🏛️ PME Backend — API RESTful Laravel 11
Plateforme de gestion organisationnelle du Parti du Maroc Émergent (PME / حزب المغرب الصاعد)
Développé par WANDICH Achraf · Stage de fin d'études @ Mediatower · 2025–2026
Encadrants : F. GMIRA & R. QESMI · Directeur : M. Hafid El Maktoub
Client : M. Ali Amzine — Parti du Maroc Émergent

📖 Contexte du projet
Ce projet a été réalisé dans le cadre d'un stage de fin d'études au sein de la société Mediatower, entreprise marocaine fondée en 2023, spécialisée dans les solutions numériques et l'intelligence artificielle, dirigée par M. Hafid El Maktoub.
Le projet a été commandité par M. Ali Amzine, fondateur et président du Parti du Maroc Émergent (PME / حزب المغرب الصاعد) — mouvement politique innovant prônant l'intelligence collective, une approche anti-populiste et une vision stratégique réaliste pour le développement du Maroc.
🔍 Problématique
Avant ce projet, la gestion des activités du PME reposait sur des outils dispersés et non centralisés :

📊 Fichiers Excel pour le suivi des membres
📱 Réseaux sociaux pour la communication
📞 Appels téléphoniques pour la coordination des événements

Cette situation engendrait :

❌ Absence d'un annuaire centralisé des membres, sympathisants et bénévoles
❌ Manque de traçabilité des activités, événements et rapports
❌ Absence de visibilité hiérarchique entre niveaux national, régional et local
❌ Aucun mécanisme structuré pour les donations et les adhésions
❌ Difficulté à orchestrer les comités spécialisés (brainstorming collectif du PME)

🎯 Objectifs

✅ Gestion centralisée des utilisateurs selon une hiérarchie multi-niveaux
✅ Création, publication et suivi des événements à l'échelle nationale / régionale / locale
✅ Gestion des inscriptions et des participants aux événements
✅ Cycle de vie complet des rapports d'activité (pending → validé / refusé → archivé)
✅ Gestion des médias liés aux activités du parti
✅ Sondages internes pour la consultation démocratique des membres
✅ Gestion des donations et contributions financières
✅ Journal d'audit automatique pour la traçabilité complète
✅ Tableau de bord statistique adapté à chaque niveau hiérarchique
✅ Interface publique de présentation du PME et de son programme


⚡ Stack technique
TechnologieVersionRôle🐘 PHP8.2Langage serveur🔴 Laravel11Framework API-MVC🔐 Laravel Sanctum—Personal Access Tokens🗄️ Eloquent ORM—Mapping objet-relationnel🐬 MySQL—Base de données relationnelle (29 tables)📦 Composer—Gestionnaire de dépendances PHP

📋 Prérequis

PHP >= 8.2
Composer
MySQL
Extensions PHP : pdo, pdo_mysql, mbstring, openssl, json, bcmath


🚀 Installation
bash# 1. Cloner le dépôt
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

🌐 L'API est accessible sur http://127.0.0.1:8000/api


🗂️ Structure du projet
pme_backend/
├── 📁 app/
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/         # 20 contrôleurs métier
│   │   │   ├── AuthController.php
│   │   │   ├── EventController.php
│   │   │   ├── ReportController.php
│   │   │   ├── PollController.php
│   │   │   ├── DonationController.php
│   │   │   ├── MediaController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── StatsController.php
│   │   │   └── ...
│   │   │   └── 📁 Concerns/
│   │   │       ├── ScopesByPartyBranch.php   # 🔍 Filtrage par branche
│   │   │       └── RecordsAuditLogs.php      # 📝 Traçabilité automatique
│   │   ├── 📁 Middleware/
│   │   │   └── CheckRole.php       # 🛡️ Contrôle d'accès par rôle
│   │   ├── 📁 Requests/            # 9 Form Requests de validation
│   │   └── 📁 Resources/           # Formatage JSON des réponses
│   ├── 📁 Models/                  # 20 modèles Eloquent
│   │   ├── User.php · Event.php · Poll.php
│   │   ├── PartyBranch.php · Report.php
│   │   ├── Donation.php · Media.php
│   │   └── ...
│   └── 📁 Services/
│       ├── NotificationService.php
│       └── SimplePdfService.php
├── 📁 database/
│   ├── 📁 migrations/              # 40+ migrations horodatées
│   └── 📁 seeders/                 # AdminSeeder · RoleSeeder · StaticPageSeeder
├── 📁 routes/
│   └── api.php                     # 🗺️ Point d'entrée unique des routes API
└── 📁 config/
    ├── sanctum.php · cors.php · auth.php
    ├── database.php · mail.php
    └── ...

🛣️ Principaux endpoints API
MéthodeEndpointDescription🔐 AuthPOST/api/loginAuthentification — obtention du token❌POST/api/logoutDéconnexion — révocation du token✅GET/api/userProfil de l'utilisateur connecté✅GET/POST/api/eventsListe / Création d'événements✅POST/api/events/{id}/registerInscription à un événement✅GET/POST/api/events/{id}/recapsRécapitulatifs post-événement✅GET/POST/api/reportsRapports d'activité✅GET/POST/api/newsActualités✅GET/POST/api/pollsSondages✅POST/api/polls/{id}/voteVote (unique par utilisateur)✅GET/POST/api/donationsDonations✅POST/api/membership-requestsDemande d'adhésion❌GET/api/statsStatistiques tableau de bord✅GET/api/audit-logsJournal d'audit (Super Admin uniquement)✅GET/POST/api/mediaMédias✅GET/POST/api/usersGestion utilisateurs (admin)✅GET/POST/api/branchesBranches géographiques du parti✅GET/api/notificationsNotifications internes✅

🔐 Authentification — Laravel Sanctum
Toutes les requêtes protégées doivent inclure l'en-tête :
httpAuthorization: Bearer {token}
Réponse au login :
json{
  "token": "...",
  "user": { "id": 1, "name": "..." },
  "role": "admin",
  "branch": { "id": 1, "name": "..." }
}
Protection anti-brute-force : throttle:6,1 — max 6 tentatives par minute.

👥 Hiérarchie des rôles
🔴 Super Admin      →  Accès total — portée nationale
🟠 Admin Régional   →  Filtré sur sa région — supervise les admins locaux
🟡 Admin Local      →  Filtré sur sa structure locale
🟢 Membre           →  Accès personnel — événements, sondages, profil
🔵 Bénévole         →  Participation aux événements
Le middleware CheckRole et le trait ScopesByPartyBranch garantissent l'étanchéité entre niveaux hiérarchiques.

📊 Codes de réponse HTTP
CodeStatutSignification200✅ OKSuccès201✅ CreatedRessource créée204✅ No ContentSuppression / mise à jour sans contenu401❌ UnauthorizedToken absent ou invalide403❌ ForbiddenRôle insuffisant — CheckRole422⚠️ UnprocessableErreur de validation — Form Request429⚠️ Too Many RequestsRate limiting déclenché500🔥 Server ErrorErreur interne non anticipée

🛡️ Sécurité

🔑 Sanctum (auth:sanctum) sur toutes les routes protégées
🔒 bcrypt via Hash::make() — aucun mot de passe stocké en clair
🚦 Rate limiting throttle:6,1 contre le brute-force
✅ Form Requests — validation systématique avant tout traitement
🌐 CORS configuré dans config/cors.php
🗑️ Soft delete sur users et donations — historique préservé
📋 RecordsAuditLogs — traçabilité automatique de toutes les actions sensibles
🔏 Credentials dans .env — jamais dans le code source


🗃️ Base de données
29 tables MySQL — InnoDB / utf8mb4_unicode_ci (support trilingue FR/AR/EN)
CatégorieTables👤 Utilisateursusers · roles · party_branches📅 Événementsevents · event_registrations · event_recaps📰 Contenunews · media · static_pages🗳️ Démocratiepolls · poll_options · votes💰 Financesdonations · membership_requests📋 Suivireports · audit_logs · notifications👥 Communautésympathizers · volunteers · contacts · newsletter_subscribers⚙️ Laravelmigrations · personal_access_tokens · cache · jobs · sessions

⚙️ Variables d'environnement
envAPP_NAME=PME
APP_ENV=local
APP_KEY=                              # généré via artisan key:generate
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

<sub>
Projet académique — Stage de fin d'études ·
**WANDICH Achraf** · Département Informatique, École Supérieure de Technologie ·
Client : M. Ali Amzine — Parti du Maroc Émergent ·
Entreprise d'accueil : **Mediatower** — M. Hafid El Maktoub · 2025–2026
</sub>
