# Touche pas au klaxon

Application web **MVC en PHP** de covoiturage entre les agences d'une entreprise.

Les employés sont importés **en lecture seule** depuis `database/users.txt`
s'il est présent, sinon depuis `database/users_demo.txt` (jeu fictif) :
l'application ne permet ni leur création, ni leur modification, ni leur
suppression. Les agences et les trajets sont gérés par l'application. Un rôle
distinct **Administrateur** (`ROLE_ADMIN`) permet de gérer la liste des agences
et de supprimer les trajets.

---

## Démarrage rapide (mentor)

1. Installer **PHP ≥ 8.1** (testé avec PHP 8.3.28), **MariaDB** et **Composer 2**.
2. Copier `.env.example` vers `.env`, puis renseigner `DB_PASSWORD`,
   `ROOT_DB_USER` et `ROOT_DB_PASSWORD` (adapter `DB_DSN` si votre MariaDB
   n'écoute pas sur le port 3310 — voir §5).
3. Depuis la racine du projet, installer les dépendances :
   `composer install` (ou `php composer.phar install` si ce fichier est présent).
4. Démarrer MariaDB, puis initialiser la base :
   `php bin/install.php 127.0.0.1 3310 <utilisateur_admin> <mot_de_passe_admin>`
   (**destructif** : la base `covoiturage` est supprimée puis recréée).
5. Lancer le serveur : `php -S 127.0.0.1:8000 -t public run-server.php`.
6. Ouvrir <http://127.0.0.1:8000> et se connecter avec un compte de
   démonstration (§8).

Les commandes ci-dessus sont documentées à partir du code du projet ; elles
n'ont pas été réexécutées lors de la dernière mise à jour du README (voir §17).

---

## 1. Présentation

« Touche pas au klaxon » permet aux employés d'une entreprise de consulter les
trajets prévus entre les agences et de proposer leurs propres trajets, afin de
réduire le nombre de véhicules peu remplis.

- **Visiteur** : consultation de la liste des trajets à venir disposant de
  places.
- **Employé connecté** : détail d'un trajet (proposeur, téléphone, e-mail),
  création d'un trajet, modification/suppression de ses propres trajets.
- **Administrateur** : tableau de bord, liste des employés (lecture seule),
  gestion des agences, liste complète des trajets et suppression.

---

## 2. Technologies et versions

| Composant | Version / choix | Vérification |
|-----------|-----------------|--------------|
| Langage | PHP 8.3.28 (`declare(strict_types=1)`) | `php -v` ; minimum `>= 8.1` dans `composer.json` |
| Architecture | MVC maison (sans framework) | `public/`, `src/Core`, `src/Models`, `src/Controllers`, `src/Views` |
| Base de données | MariaDB (instance locale 11.4.9), InnoDB, `utf8mb4` | `.db/server-my.ini` (non versionné), `database/schema.sql` |
| Accès aux données | PDO, requêtes préparées (`PDO::ATTR_EMULATE_PREPARES = false`) | `config/config.php` |
| Styles | Sass compilé en `public/assets/css/app.css`, Bootstrap 5.3.8 local | `public/assets/` |
| Tests | PHPUnit 10.5.67 (`vendor/bin/phpunit`) | `composer.lock`, `phpunit.xml` |
| Analyse statique | PHPStan 1.12.34, **niveau 6** | `phpstan.neon` |
| Dépendances | Composer 2 (PHPUnit + PHPStan en `require-dev`) | `composer.json`, `composer.lock` |
| Génération du PDF | Python 3 + bibliothèque standard | `tools/makepdf.py` (optionnel) |

---

## 3. Prérequis et extensions PHP

- **PHP ≥ 8.1**. Le projet a été vérifié avec **PHP 8.3.28** (CLI, ZTS).
- Extensions PHP **présentes dans l'environnement de développement d'origine**
  (à vérifier sur votre poste) : `PDO`, `pdo_mysql`, `pdo_sqlite`, `mbstring`,
  `json`, `session`.
  - `pdo_mysql` est indispensable à l'application ;
  - `pdo_sqlite` ne sert qu'aux tests (base SQLite en mémoire) ;
  - `mbstring` est utilisé pour les noms et les rôles.
- **Composer 2** : le dossier `vendor/` et le fichier `composer.phar` ne sont
  pas versionnés. Il faut donc installer les dépendances sur chaque poste.
- **MariaDB** démarré et accessible, avec un compte disposant des droits de
  création de base, d'utilisateur et de `GRANT` pour l'installation.
- **WAMP** est l'environnement de développement d'origine, mais Apache n'est
  pas obligatoire : le serveur PHP intégré suffit.
- **dart-sass** uniquement si vous modifiez les sources Sass.
- **Python 3** uniquement si vous régénérez le dossier PDF.

---

## 4. Structure générale

```text
public/index.php        Contrôleur frontal : configuration, session, routage
public/.htaccess        Réécriture Apache (DocumentRoot sur public/)
run-server.php          Routeur du serveur PHP intégré
autoload.php            Autoloader PSR-4 de secours (sans Composer)
config/config.php       Chargement de .env, validation, configuration PDO/session

src/Core/               Router, Controller, Database, Request, Auth, Csrf, Flash, View
src/Models/             Employe, Agence, Trajet (accès PDO)
src/Controllers/        Home, Auth, Trajet, Agence, Admin
src/Views/              layout.php, home/, auth/, trajets/, admin/

database/schema.sql     Création de la base, des tables et de l'utilisateur applicatif
database/agences.txt    Jeu d'essais des agences (12 villes)
database/users.txt      Jeu d'essais local des employés (non versionné, s'il est présent)
database/users_demo.txt Jeu de démonstration fictif (20 identités, repli versionnable)

bin/install.php         Installation/réinitialisation de la base + jeu d'essais
bin/check.php           Diagnostic base (écrit check.log à la racine)
bin/phpunit             Lanceur du vrai PHPUnit (vendor/bin/phpunit)
bin/phpstan             Lanceur du vrai PHPStan (vendor/bin/phpstan)
bin/phpstan-check       Ancien analyseur maison (repli hors-ligne, non utilisé pour la validation)

tests/Models/           Tests des modèles (SQLite en mémoire)
tests/Core/             Tests du jeton CSRF
tests/Support/          Base de test isolée
phpunit.xml             Configuration PHPUnit
phpstan.neon            Configuration PHPStan (niveau 6)

public/assets/sass/     Sources Sass (_variables.scss, app.scss)
public/assets/css/      CSS compilé (app.css)
public/assets/vendor/   Bootstrap 5.3.8 (CSS + JS locaux, aucun CDN)

docs/MCD.png            MCD (modèle conceptuel de données)
docs/MLD.txt            MLD (modèle logique de données)
tools/makepdf.py        Génération du dossier de livraison PDF
tools/content.json      Contenu du dossier PDF

var/sessions/           Sessions PHP (créé à l'exécution, non versionné)
.env.example            Modèle de configuration (à copier vers .env)
composer.json/.lock     Dépendances de développement verrouillées
```

Fichiers **non versionnés** à recréer ou régénérer sur un autre poste :
`.env`, `vendor/`, `var/`, `.db/`, `check.log`, le PDF de livraison.

---

## 5. Configuration du fichier `.env`

La configuration est lue depuis un fichier **`.env`** à la racine du projet.
Ce fichier **n'est jamais versionné** (`.gitignore`). Partez de l'exemple :

```bash
# bash
cp .env.example .env

# PowerShell
Copy-Item .env.example .env
```

Renseignez ensuite les variables suivantes :

| Variable | Rôle | Utilisée par |
|----------|------|--------------|
| `DB_DSN` | DSN PDO complet (hôte, port, base, charset) | application |
| `DB_USER` | Compte applicatif ; doit rester `covoiturage` avec le schéma fourni | application |
| `DB_PASSWORD` | Mot de passe du compte applicatif | application + `bin/install.php` |
| `ROOT_DB_USER` | Compte administrateur du SGBD | **`bin/install.php` uniquement** |
| `ROOT_DB_PASSWORD` | Mot de passe de ce compte administrateur | **`bin/install.php` uniquement** |

Points importants :

- `config/config.php` n'accepte que `DB_DSN`, `DB_USER` et `DB_PASSWORD`.
  Toute autre clé du `.env` est ignorée par l'application.
- `bin/install.php` lit **toutes** les clés du `.env` ; c'est ainsi qu'il
  récupère `ROOT_DB_USER` et `ROOT_DB_PASSWORD`.
- Les variables déjà définies par le système/serveur ont priorité sur `.env`.
- `ROOT_DB_*` ne servent qu'à l'installation et peuvent être retirées du
  `.env` ensuite.
- Ne jamais versionner `.env` : il contient des secrets locaux.

Exemple de DSN pour l'instance locale du projet :

```text
DB_DSN=mysql:host=127.0.0.1;port=3310;dbname=covoiturage;charset=utf8mb4
```

---

## 6. Installation et initialisation de la base MariaDB

### 6.1 Pourquoi le port 3310 ?

Le projet a été développé avec une instance MariaDB dédiée, configurée dans
`.db/server-my.ini` (fichier **non versionné**) :

- `port=3310`
- `bind_address=127.0.0.1`
- base de données locale sous `.db/data`

Le port **3310** permet d'éviter un conflit avec une instance MariaDB déjà
présente sur le port par défaut **3306**. Ce n'est **pas une obligation** : vous
pouvez utiliser n'importe quel port ou n'importe quelle instance MariaDB
accessible.

Si vous changez de port :

1. adaptez `DB_DSN` dans `.env` ;
2. passez le même port à `bin/install.php` (deuxième argument), car
   `bin/install.php` utilise son propre hôte/port pour créer la base et ne lit
   pas `DB_DSN` pour cela.

### 6.2 Jeu d'essais des employés : `users.txt` ou `users_demo.txt`

`bin/install.php` sélectionne automatiquement le fichier d'employés :

1. `database/users.txt` s'il existe : jeu local, **non versionné**, susceptible
   de contenir des données réelles ;
2. sinon `database/users_demo.txt` : 20 identités fictives, emails en
   `@example.com`, téléphones dans la plage `06 39 98 00 xx` ;
3. si aucun des deux n'existe : message d'erreur explicite et arrêt **avant**
   toute connexion à MariaDB ou exécution de `schema.sql`.

Le jeu `users_demo.txt` est destiné au dépôt. Le jeu `users.txt` doit rester
local et ne jamais être publié. Format attendu, une ligne par employé :

```text
Nom,Prénom,Téléphone,Email
```

Sans `users.txt`, l'installation charge les 20 identités de démonstration et
désigne `Admin Demo` comme administrateur.

### 6.3 Lancer l'installation

```bash
# Dépendances de développement (PHPUnit, PHPStan)
composer install
# ou, si composer.phar est présent dans le dossier :
php composer.phar install

# Initialisation de la base (destructif)
php bin/install.php
```

`php bin/install.php` sans argument utilise :

- hôte `127.0.0.1` ;
- port `3310` ;
- `ROOT_DB_USER` / `ROOT_DB_PASSWORD` lus dans `.env`.

Vous pouvez tout passer en arguments (aucune valeur n'est alors écrite sur le
disque) :

```bash
php bin/install.php <hote> <port> <utilisateur_admin> <mot_de_passe_admin>

# exemple de forme :
php bin/install.php 127.0.0.1 3310 root ********
```

> **Attention** : `bin/install.php` exécute `database/schema.sql`, qui contient
> `DROP DATABASE IF EXISTS covoiturage`. **La base existante est supprimée puis
> recréée.** Ne lancez cette commande que sur une base de démonstration.

### 6.4 Ce que fait `bin/install.php`

1. exécute `database/schema.sql` :
   - crée la base `covoiturage` en `utf8mb4` ;
   - crée les tables `agences`, `employes`, `trajets` et leurs contraintes ;
   - crée l'utilisateur applicatif `covoiturage` (sans mot de passe à ce stade) ;
2. applique `DB_PASSWORD` au compte `covoiturage@localhost` et
   `covoiturage@127.0.0.1` via `ALTER USER` ;
3. importe les **12 agences** ;
4. importe les **20 employés** du fichier retenu : avec `users.txt`,
   l'administrateur est l'employé désigné par la règle historique du code ;
   avec `users_demo.txt`, c'est `Admin Demo` ;
5. crée **6 trajets de démonstration** sur les jours suivants ;
6. affiche en fin d'exécution l'administrateur et un exemple d'employé du
   fichier réellement utilisé.

Les identifiants issus de `users.txt` sont locaux et ne doivent pas être
publiés ni recopiés dans le README ou le dossier de livraison.

Remarques vérifiées dans le code :

- `bin/install.php` crée la connexion applicative avec le port passé en
  argument (ou `3310`) et le nom de base `covoiturage`, **pas** avec le
  `DB_DSN` du `.env` ;
- `bin/install.php` utilise le compte `covoiturage` et le `DB_PASSWORD` du
  `.env` ; `DB_USER` doit donc rester `covoiturage` avec le schéma fourni ;
- jouer `database/schema.sql` seul ne définit pas le mot de passe applicatif :
  il faut ensuite `ALTER USER` ou passer par `bin/install.php`.

### 6.5 Rotation du mot de passe MariaDB

Procédure, à exécuter avec un compte administrateur du SGBD (jamais avec le
compte applicatif) :

1. générer un mot de passe fort et unique ;
2. l'appliquer aux deux comptes applicatifs :

```sql
ALTER USER 'covoiturage'@'localhost' IDENTIFIED BY '<nouveau_mot_de_passe>';
ALTER USER 'covoiturage'@'127.0.0.1' IDENTIFIED BY '<nouveau_mot_de_passe>';
FLUSH PRIVILEGES;
```

3. reporter la même valeur dans `DB_PASSWORD` du `.env` local ;
4. redémarrer PHP et le serveur web afin de recharger la configuration ;
5. vérifier avec `php bin/check.php`.

Après cette rotation, l'ancien mot de passe est **invalidé côté MariaDB**.
L'historique Git n'a **pas** été réécrit : il peut encore contenir l'ancien
secret. Une purge d'historique serait une opération destructive distincte, non
réalisée ici. Ne réutilisez jamais l'ancien mot de passe.

---

## 7. Démarrage du serveur PHP

Depuis la racine du projet :

```bash
php -S 127.0.0.1:8000 -t public run-server.php
```

Puis ouvrir : **<http://127.0.0.1:8000>**

- `-t public` est indispensable : la racine web est `public/`, ce qui permet de
  servir les fichiers statiques (`/assets/css/app.css`,
  `/assets/vendor/bootstrap/...`) tout en routant le reste via
  `run-server.php`, puis `public/index.php`.
- Si le port `8000` est occupé, choisissez-en un autre
  (`php -S 127.0.0.1:8080 -t public run-server.php`).
- Pour exposer l'application à une autre machine du réseau local, écoutez sur
  `0.0.0.0` et autorisez le port dans le pare-feu, uniquement sur un réseau de
  confiance.
- L'application tente de créer `var/sessions/` pour stocker les sessions. Le
  dossier `var/` doit être inscriptible par le compte PHP.

### Sous Apache

`public/.htaccess` active `mod_rewrite` et désactive l'affichage des dossiers.
Le `DocumentRoot` Apache doit pointer sur `public/`, avec `AllowOverride`
autorisé pour ce dossier. Sans cela, les URL autres que `/` renverront une
erreur 404.

---

## 8. Comptes de démonstration

### 8.1 Mode démonstration (sans `database/users.txt`)

| Rôle | Adresse email | Mot de passe |
|------|---------------|--------------|
| Administrateur | `admin.demo@example.com` | `Admin_2024!` |
| Employé | `usager01@example.com` | `Passw0rd!` |

Les autres comptes sont `usager02@example.com` à `usager19@example.com`, avec
le même mot de passe employé. Toutes ces identités sont fictives et utilisent
le domaine réservé `example.com`.

### 8.2 Mode jeu local (`database/users.txt` présent)

Les 20 comptes proviennent du fichier local. L'administrateur est l'employé
désigné par la règle historique de `bin/install.php` ; les autres reçoivent
`ROLE_USER`. Les emails et mots de passe de ce fichier sont locaux : ne les
publiez pas et ne les recopiez ni dans le README ni dans le dossier de
livraison. `bin/install.php` affiche l'administrateur et un exemple d'employé
du fichier réellement utilisé en fin d'installation.

Dans les deux modes, les mots de passe sont stockés hachés (`password_hash`) et
vérifiés avec `password_verify`. Ces comptes sont réservés à la démonstration :
changez les mots de passe avant toute utilisation réelle.

---

## 9. Fonctionnalités principales

### Visiteur

- Page d'accueil listant les trajets **futurs** disposant encore de places,
  triés par date de départ croissante.
- Connexion / déconnexion.

### Employé connecté

- Consultation du détail d'un trajet : agences, dates, places, proposeur,
  téléphone et e-mail (réservé aux utilisateurs connectés).
- Création d'un trajet avec les informations du compte pré-remplies et non
  modifiables.
- Modification et suppression de **ses propres** trajets.
- Validation métier : agences distinctes, arrivée postérieure au départ, places
  cohérentes.
- Jetons CSRF sur les formulaires de modification.

### Administrateur (`ROLE_ADMIN`)

- Tableau de bord : compteurs employés / agences / trajets.
- Liste des employés en **lecture seule** (données RH).
- Gestion des agences : liste, création, modification, suppression avec
  messages d'erreur (doublon, agence utilisée par un trajet).
- Liste complète des trajets et suppression d'un trajet, avec CSRF.

---

## 10. Vérifications, tests et analyse statique

### Diagnostic de la base

```bash
php bin/check.php
```

- Lit `DB_DSN`, `DB_USER`, `DB_PASSWORD` dans `.env` (même liste blanche que
  `config/config.php`).
- Exécute trois `COUNT(*)` : agences, employés, trajets.
- Écrit un journal `check.log` à la racine du projet (non servi par le serveur
  web, ignoré par Git).
- Codes de sortie : `0` succès ; `1` configuration/journal indisponible ;
  `2` échec de connexion ou de comptage.
- Le journal ne contient que des compteurs et des messages génériques, jamais
  de mot de passe, de DSN, d'e-mail ni de trace.

### Tests PHPUnit

```bash
php bin/phpunit
```

- `bin/phpunit` délègue à `vendor/bin/phpunit` lorsque Composer est installé.
- `phpunit.xml` charge `vendor/autoload.php` : `composer install` est donc
  nécessaire.
- Les tests utilisent une base **SQLite en mémoire**
  (`tests/Support/TestDatabase.php`) : ils ne touchent pas la base MariaDB.
- 11 méthodes de test sont présentes : 4 CSRF, 3 Employe, 4 Trajet.
- Résultat de l'exécution du 25/11/2026 : `OK (11 tests, 25 assertions)`.

### Analyse statique PHPStan

```bash
php bin/phpstan
```

- Lance le **vrai** PHPStan de `vendor/bin/phpstan`.
- Configuration `phpstan.neon` : **niveau 6**, chemins `src` et `bin/check.php`.
- Exécution du 25/11/2026 au niveau 1 : `[OK] No errors` (aucune erreur).
- `bin/phpstan-check` est un ancien outil maison conservé comme repli hors
  ligne ; il n'est pas utilisé pour la validation.

### Syntaxe PHP

```bash
php -l chemin/vers/fichier.php
```

À utiliser sur les fichiers modifiés ; ne remplace ni les tests ni PHPStan.

---

## 11. Difficultés courantes et solutions

| Symptôme | Cause probable | Piste de correction |
|---|---|---|
| `SQLSTATE[HY000] [2002] Connection refused` | MariaDB arrêté, mauvais hôte ou mauvais port | Démarrer MariaDB, vérifier `DB_DSN`, vérifier que le port (3310 ou autre) est bien celui de l'instance |
| `SQLSTATE[HY000] [1045] Access denied for user` | `DB_USER` / `DB_PASSWORD` incorrects | Renseigner le mot de passe du compte `covoiturage` ; `bin/install.php` applique `DB_PASSWORD` au compte (opération destructive) |
| `Unknown database 'covoiturage'` | Base non initialisée | Lancer `php bin/install.php` sur une base de démonstration |
| Le port 3310 ne répond pas | Instance dédiée non démarrée ou configurée sur un autre port | Démarrer l'instance, ou utiliser MariaDB sur 3306 en adaptant `DB_DSN` **et** le port passé à `bin/install.php` |
| `Configuration DB manquante ou vide` | `.env` absent, incomplet ou mal formé | Copier `.env.example` vers `.env`, renseigner les trois variables `DB_*`, vérifier l'absence de guillemets involontaires |
| `Paramètres root DB manquants` | `ROOT_DB_USER` / `ROOT_DB_PASSWORD` absents | Les ajouter au `.env` ou passer les quatre arguments à `bin/install.php` |
| `php` n'est pas reconnu | PHP absent du `PATH` | Ajouter PHP au `PATH` ou appeler l'exécutable complet (ex. `C:\wamp64\bin\php\php8.3.28\php.exe`) |
| Les pages internes renvoient 404 / pas de CSS | Serveur lancé sans `-t public`, ou DocumentRoot Apache mal réglé | Relancer avec `php -S 127.0.0.1:8000 -t public run-server.php` ; pointer Apache sur `public/` |
| `Permission denied` sur les sessions | `var/` non inscriptible | Créer `var/sessions/` avec des droits adaptés au compte PHP |
| `composer` introuvable / `vendor` absent | Composer non installé ou dépendances non récupérées | Installer Composer 2 puis `composer install` ; `composer.phar` local n'est pas versionné |
| PHPUnit échoue sur `pdo_sqlite` | Extension SQLite désactivée | Activer `pdo_sqlite` dans `php.ini` |
| `bin/check.php` sort en code 2 | Connexion PDO ou comptage impossible | Lire `check.log`, vérifier MariaDB, la base et les droits |
| Port 8000 déjà utilisé | Autre serveur en cours | Choisir un autre port pour `php -S` |

---

## 12. Reprise du projet sur un autre ordinateur

### À copier

Copiez le dossier du projet **sans** `.env`, `vendor/`, `var/`, `.db/`,
`check.log` ni le PDF généré. Vérifiez que la copie contient au minimum :

- `composer.json`, `composer.lock` ;
- `database/schema.sql`, `database/agences.txt` ;
- `database/users.txt` si vous possédez le jeu local (à ne jamais publier),
  sinon `database/users_demo.txt` pour le mode démonstration ;
- `docs/MCD.png`, `docs/MLD.txt` ;
- `public/assets/` (CSS compilé et Bootstrap local) ;
- `src/`, `config/`, `tests/`, `bin/`, `public/index.php`, `run-server.php` ;
- `.env.example`.

### Procédure

1. Installer PHP ≥ 8.1 avec les extensions de §3, MariaDB et Composer 2.
2. Copier `.env.example` vers `.env` et renseigner les valeurs **locales**
   (ne jamais réutiliser le `.env` d'un autre poste).
3. Lancer `composer install`.
4. Démarrer MariaDB et vérifier le port retenu.
5. Lancer `php bin/install.php` avec le bon hôte, le bon port et un compte
   administrateur SGBD (ou renseigner `ROOT_DB_*` dans `.env`).
6. Lancer `php -S 127.0.0.1:8000 -t public run-server.php`.
7. Se connecter avec un compte de démonstration, puis vérifier les pages
   publiques et l'espace `/admin`.
8. Exécuter `php bin/phpunit` et `php bin/phpstan` si `vendor/` est installé.
9. Exécuter `php bin/check.php` pour vérifier la connexion et les compteurs.

### Différences Windows / Linux

- Les commandes de copie diffèrent (`Copy-Item` / `cp`), pas le reste.
- Le port 3310 et les chemins de `.db/server-my.ini` sont propres à
  l'installation d'origine ; adaptez-les à votre MariaDB.
- WAMP n'est pas nécessaire : PHP CLI + MariaDB suffisent.
- Sous Apache, le `DocumentRoot` doit pointer sur `public/` et `mod_rewrite`
  doit être actif.

---

## 13. Charte graphique

La palette est **imposée** et **centralisée dans un seul fichier Sass** :
`public/assets/sass/_variables.scss`.

| Rôle | Variable | Valeur |
|------|----------|--------|
| Bleu principal | `$primary` | `#0074c7` |
| Bleu foncé | `$primary-dark` | `#00497c` |
| Ardoise | `$secondary` | `#384050` |
| Vert | `$success` | `#82b864` |
| Rouge | `$danger` | `#cd2c2e` |
| Fond clair | `$light` | `#f1f8fc` |

Aucune couleur n'est écrite directement dans les vues : les composants
consomment les propriétés CSS `--tpk-*`. Un autre site de l'entreprise peut donc
réutiliser la feuille de style en ne changeant que ce fichier.

Pour recompiler la feuille de style (nécessite dart-sass) :

```bash
sass --no-source-map public/assets/sass/app.scss public/assets/css/app.css
```

Bootstrap 5.3.8 est servi **localement** (`public/assets/vendor/bootstrap/`) :
aucun accès CDN n'est requis à l'exécution. Il est chargé avant `app.css`
dans `src/Views/layout.php`, ce qui permet à la charte du projet de surcharger
les composants Bootstrap.

---

## 14. Modèle de données

- **AGENCE** (`id`, `ville`) — la ville est unique.
- **EMPLOYE** (`id`, `nom`, `prenom`, `email`, `telephone`, `role`, `mot_de_passe`).
- **TRAJET** (`id`, `auteur_id`, `agence_depart_id`, `agence_arrivee_id`,
  `date_depart`, `date_arrivee`, `nb_places_total`, `nb_places_disponibles`).

Règles vérifiées à la fois par l'application et par la base :

- l'agence de départ doit être **différente** de l'agence d'arrivée ;
- la date d'arrivée doit être **strictement postérieure** au départ ;
- le nombre de places disponibles est compris entre 0 et le nombre total de places.

Livrables : **[docs/MCD.png](docs/MCD.png)** (modèle conceptuel) et
**[docs/MLD.txt](docs/MLD.txt)** (modèle logique).

---

## 15. Dossier de livraison PDF

Le dossier PDF se régénère avec (bibliothèque standard Python uniquement) :

```bash
python tools/makepdf.py tools/content.json Nom_Prenom_Titre_devoir.pdf
```

Il contient la présentation, les fonctionnalités, l'architecture, le modèle de
données, le MCD et le MLD, la charte graphique, la procédure d'installation, les
comptes de démonstration, les résultats des tests et les limites connues.

Le PDF n'est pas versionné et comporte encore deux champs personnels à
compléter (nom de l'étudiant, URL du dépôt GitHub).

---

## 16. Sécurité et limites connues

**Mesures en place**

- Mots de passe hachés (`password_hash` / `password_verify`).
- Requêtes SQL préparées (`PDO::ATTR_EMULATE_PREPARES = false`) et échappement
  HTML systématique (`htmlspecialchars`) dans les vues.
- Jeton **CSRF** (`src/Core/Csrf.php`) vérifié sur tous les formulaires qui
  modifient des données : connexion, création/modification/suppression de
  trajet, création/modification/suppression d'agence, suppression admin.
- Session : cookie `HttpOnly`, `SameSite=Lax`, `Secure` automatique en HTTPS,
  `session.use_strict_mode`, régénération de l'identifiant à la connexion.
- `/trajets/{id}` est réservé aux utilisateurs connectés : les coordonnées du
  proposeur ne sont pas accessibles anonymement.
- Identifiants de la base lus dans `.env` (non versionné) ; aucun mot de passe
  de base en clair dans `config/config.php` ni `database/schema.sql`.
- `check.log` ne contient que des compteurs et des messages génériques.

**Limites assumées**

- Aucune fonction de **réservation** de place : le nombre de places disponibles
  est ajusté par l'auteur du trajet lors de la modification.
- Les comptes de démonstration utilisent des mots de passe connus
  (`Admin_2024!` / `Passw0rd!`) : ils sont exigés par le cahier des charges
  pour la démonstration et **doivent être changés** en production.
- L'ancien mot de passe de base est invalidé côté MariaDB après la rotation
  décrite au §6.5, mais l'historique Git n'a **pas** été réécrit : il peut
  encore contenir cet ancien secret. Une purge d'historique serait une
  opération destructive distincte, non réalisée dans ce cadre.
- Le dossier PDF de livraison comporte encore deux champs personnels à
  compléter (nom de l'étudiant, URL du dépôt GitHub).
- `bin/check.php` écrit `check.log` à la racine du projet ; ce fichier est
  ignoré par Git mais doit être protégé par les droits du dossier.

---

## 17. État de vérification de cette documentation

Mise à jour du README : **25/11/2026**.

Vérifié par lecture et commandes en lecture seule :

- version PHP (`php -v`) et extensions (`php -m`) ;
- versions PHPUnit/PHPStan dans `composer.lock` ;
- présence de `vendor/bin/phpunit`, `vendor/bin/phpstan`, `composer.lock`,
  `phpunit.xml`, `phpstan.neon`, `public/.htaccess`, `docs/`, `tests/` ;
- comportement de `bin/install.php`, `bin/check.php`, `run-server.php`,
  `config/config.php`, `database/schema.sql` ;
- routes déclarées dans `public/index.php` ;
- présence de 12 agences, 20 employés et 11 méthodes de test.

**Non exécuté lors de cette mise à jour** : `bin/install.php`, `bin/check.php`,
toute connexion MariaDB. Les résultats de tests cités au §10 proviennent de
l'exécution réelle du 25/11/2026 (PHPUnit 11 tests / 25 assertions, PHPStan
niveau 1 sans erreur) ; `php -l bin/install.php` et `git diff --check` ont
également été exécutés.

À confirmer sur votre poste : version exacte de MariaDB, exécution réelle de
l'installation, résultats PHPUnit/PHPStan, et présence dans le dépôt des
fichiers `docs/`, `.env.example` et `database/users_demo.txt`.
