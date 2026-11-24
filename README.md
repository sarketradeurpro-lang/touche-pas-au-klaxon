# Touche pas au klaxon

Application web **MVC en PHP** de covoiturage entre les agences d'une entreprise.

Les employés sont importés **en lecture seule** depuis un fichier
(`database/users.txt`) : l'application ne permet ni leur création, ni leur
modification, ni leur suppression. Les agences et les trajets sont gérés par
l'application. Un rôle distinct **Administrateur** (`ROLE_ADMIN`) permet de
gérer la liste des agences et de supprimer les trajets.

---

## 1. Stack technique

| Composant | Version / choix |
|-----------|-----------------|
| Langage | PHP 8.3.28 (`declare(strict_types=1)`) |
| Architecture | MVC maison (sans framework) |
| Base de données | MariaDB 11.4.9, moteur InnoDB, `utf8mb4` |
| Accès aux données | PDO, requêtes préparées (`PDO::ATTR_EMULATE_PREPARES = false`) |
| Styles | Sass (`public/assets/sass`) compilé en `public/assets/css/app.css`, Bootstrap 5.3.8 local |
| Tests | PHPUnit 10.5.67 (`vendor/bin/phpunit`, base SQLite en mémoire) |
| Analyse statique | PHPStan 1.12.34, **niveau 6**, configuration `phpstan.neon` |
| Dépendances | Composer (PHPUnit + PHPStan en `require-dev`) ; Bootstrap servi localement |

---

## 2. Prérequis

- **WAMP** (ou équivalent) avec **PHP ≥ 8.1** et **MariaDB ≥ 10.11**.
- Extensions PHP : `pdo`, `pdo_mysql`, `mbstring`, `json`, `session`.
- Pour les tests uniquement : `pdo_sqlite` (base en mémoire, aucune donnée réelle touchée).
- **Composer 2** pour installer PHPUnit et PHPStan (`php composer.phar install`).
- Un port TCP libre pour MariaDB. **Configuration du projet : port `3310`**
  (voir `.env` et `.db/server-my.ini`). Si votre MariaDB écoute sur le port
  par défaut `3306`, adaptez le DSN dans `.env`.

---

## 3. Installation

### 3.1 Configurer l'environnement `.env`

La configuration est lue depuis un fichier **`.env`** à la racine du projet.
Ce fichier **n'est jamais versionné** (`.gitignore`). Partez de l'exemple :

```bash
cp .env.example .env      # bash
Copy-Item .env.example .env   # PowerShell
```

Renseignez ensuite les variables suivantes :

| Variable | Rôle | Utilisée par |
|----------|------|--------------|
| `DB_DSN` | DSN PDO complet (hôte, port, nom de base, charset) | application + `bin/install.php` |
| `DB_USER` | Compte applicatif (jamais `root`) | application + `bin/install.php` |
| `DB_PASSWORD` | Mot de passe du compte applicatif | application + `bin/install.php` |
| `ROOT_DB_USER` | Compte administrateur du SGBD | **`bin/install.php` uniquement** |
| `ROOT_DB_PASSWORD` | Mot de passe de ce compte administrateur | **`bin/install.php` uniquement** |

`config/config.php` n'accepte que les trois premières variables. Les deux
variables `ROOT_DB_*` ne servent qu'à créer la base lors de l'installation :
elles ne sont jamais utilisées par l'application en cours d'exécution et
peuvent être retirées du `.env` après l'installation.

### 3.2 Installer les dépendances et créer la base

```bash
cd tpfk

# 1. Dépendances de développement (PHPUnit, PHPStan)
php composer.phar install

# 2. Créer la base, les tables, l'utilisateur applicatif et charger le jeu d'essais
php bin/install.php
```

`bin/install.php` lit `ROOT_DB_USER` / `ROOT_DB_PASSWORD` dans `.env`. Vous
pouvez aussi les passer en arguments (aucune valeur n'est alors écrite sur le
disque) :

```bash
php bin/install.php <hote> <port> <utilisateur_admin> <mot_de_passe_admin>
# exemple de forme : php bin/install.php 127.0.0.1 3310 mon_admin ********
```

Cette commande :

1. exécute `database/schema.sql` (création de la base `covoiturage`, des trois
   tables, des contraintes et de l'utilisateur applicatif `covoiturage`) ;
2. applique `DB_PASSWORD` au compte applicatif (`ALTER USER`) ;
3. importe les **12 agences** de `database/agences.txt` ;
4. importe les **20 employés** de `database/users.txt`, en attribuant le rôle
   `ROLE_ADMIN` à Alexandre Martin ;
5. crée **6 trajets de démonstration** répartis sur les jours suivants.

> **Attention** : `bin/install.php` **supprime puis recrée** la base. Il remet
> donc les données de démonstration à leur état initial.

---

## 4. Lancement

```bash
php -S 127.0.0.1:8000 -t public run-server.php
```

Puis ouvrir : **<http://127.0.0.1:8000>**

Le paramètre `-t public` est indispensable : il place la racine du serveur dans
`public/`, ce qui permet de servir les fichiers statiques (`/assets/css/app.css`,
`/assets/vendor/bootstrap/...`) tout en faisant passer les autres URL par
`run-server.php`, puis par le contrôleur frontal `public/index.php`.

Sous Apache + mod_rewrite, le fichier `public/.htaccess` fait la même chose et
aucune commande de lancement n'est nécessaire.

---

## 5. Comptes de démonstration

| Rôle | Adresse email | Mot de passe |
|------|---------------|--------------|
| Administrateur | `alexandre.martin@email.fr` | `Admin_2024!` |
| Employé | `maxime.petit@email.fr` | `Passw0rd!` |

Les autres employés de `database/users.txt` utilisent le mot de passe
`Passw0rd!`. Les mots de passe sont stockés hachés (`password_hash`) et
vérifiés avec `password_verify`.

---

## 6. Organisation du code

```
public/index.php        Contrôleur frontal : configuration, session, routage
run-server.php          Script routeur du serveur PHP intégré
autoload.php            Autoload PSR-4 maison (préfixe TouchePasAuKlaxon\)

src/Core/               Router, Controller, Database, Request, Auth, Csrf, Flash, View
src/Models/             Employe, Agence, Trajet (accès PDO)
src/Controllers/        Home, Auth, Trajet, Agence, Admin
src/Views/              layout.php, home/, auth/, trajets/, admin/

database/schema.sql     Création de la base et des tables
database/agences.txt    Jeu d'essais des agences (12 villes)
database/users.txt      Jeu d'essais des employés (20 personnes)

bin/install.php         Installation + alimentation de la base
bin/check.php           Diagnostic (connexion, compteurs)
bin/phpunit             Lanceur du vrai PHPUnit (vendor/bin/phpunit)
bin/phpstan             Lanceur du vrai PHPStan (vendor/bin/phpstan)
bin/phpstan-check       Ancien analyseur maison (conservé comme repli hors-ligne)

tests/Models/           Tests des modèles (base SQLite en mémoire)
tests/Core/             Tests du jeton CSRF
tests/Support/          Base de test isolée (TestDatabase)
phpunit.xml             Configuration PHPUnit
phpstan.neon            Configuration PHPStan (niveau 6)

public/assets/vendor/   Bootstrap 5.3.8 (CSS + JS servis localement)
tools/makepdf.py        Génération du dossier de livraison PDF
tools/content.json      Contenu du dossier PDF

docs/MCD.png            MCD (modèle conceptuel de données)
docs/MLD.txt            MLD (modèle logique de données, format textuel)
```

---

## 7. Charte graphique

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

## 8. Modèle de données

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

## 9. Tests et analyse statique

```bash
php bin/phpunit                   # tests unitaires (modèles + CSRF), base SQLite en mémoire
php bin/phpstan                   # analyse statique officielle (niveau 6, phpstan.neon)
php bin/check.php                 # diagnostic de la base (nécessite MariaDB démarré)
```

Résultats réels constatés le 24/11/2026 avec PHP 8.3.28 :

```
PHPUnit 10.5.67 — OK (11 tests, 25 assertions)
PHPStan 1.12.34 — niveau 6 — [OK] No errors
```

Points importants :

- les tests utilisent **le vrai PHPUnit** installé par Composer et une base
  **SQLite en mémoire** créée par `tests/Support/TestDatabase.php` : ils ne
  touchent **jamais** la base MariaDB `covoiturage` ;
- `bin/phpunit` et `bin/phpstan` délèguent aux binaires `vendor/bin/*` ;
  `bin/phpstan-check` (ancien outil maison) n'est plus utilisé pour la
  validation et reste seulement comme repli sans Composer ;
- PHPStan est configuré par `phpstan.neon` (niveau 6). Aucun autre niveau
  n'est revendiqué.

---

## 10. Dossier de livraison

Le dossier PDF se régénère avec (bibliothèque standard Python uniquement) :

```bash
python tools/makepdf.py tools/content.json Nom_Prenom_Titre_devoir.pdf
```

Il contient la présentation, les fonctionnalités, l'architecture, le modèle de
données, le MCD et le MLD, la charte graphique, la procédure d'installation, les
comptes de démonstration, les résultats des tests et les limites connues.

---

## 11. Sécurité et limites connues

**Mesures en place**

- Mots de passe hachés (`password_hash` / `password_verify`).
- Requêtes SQL préparées (`PDO::ATTR_EMULATE_PREPARES = false`) et échappement
  HTML systématique (`htmlspecialchars`) dans les vues.
- Jeton **CSRF** (`src/Core/Csrf.php`) vérifié sur tous les formulaires qui
  modifient des données : connexion, création/modification/suppression de
  trajet, création/modification/suppression d'agence, suppression admin.
- Session : cookie `HttpOnly`, `SameSite=Lax`, `Secure` automatique en HTTPS,
  régénération de l'identifiant à la connexion.
- `/trajets/{id}` est réservé aux utilisateurs connectés : les coordonnées du
  proposeur ne sont plus accessibles anonymement.
- Identifiants de la base lus dans `.env` (non versionné), plus aucun mot de
  passe en clair dans `config/config.php` ni `database/schema.sql`.

**Limites assumées**

- Aucune fonction de **réservation** de place : le nombre de places disponibles
  est ajusté par l'auteur du trajet lors de la modification.
- Les comptes de démonstration utilisent des mots de passe connus
  (`Admin_2024!` / `Passw0rd!`) : ils sont exigés par le cahier des charges
  pour la démonstration et **doivent être changés** en production.
- L'historique Git contient encore un ancien mot de passe de base (commit
  initial). La purge de l'historique est une opération destructive distincte,
  volontairement non réalisée.
- Le dossier PDF de livraison comporte encore deux champs personnels à
  compléter (nom de l'étudiant, URL du dépôt GitHub).
