# Dossier d'audit technique — « Touche pas au klaxon »

> **Statut : document historique, partiellement obsolète.**
> Ce dossier a été rédigé lors d'un audit antérieur. Il décrivait l'état du
> dépôt **avant** le passage de sécurisation (branche `securisation-projet`,
> installation Composer, corrections CSRF, base de test SQLite, Bootstrap).
> Il est conservé pour mémoire ; **le README fait foi** pour l'état courant.
> Les affirmations ci-dessous ont été corrigées le 24/11/2026.

---

## 0. Ce qui a changé depuis la première rédaction

| Affirmation de l'ancien dossier | État réel au 24/11/2026 |
|---|---|
| Branche `master`, arbre de travail propre | Branche courante `securisation-projet`, travail **non commité** (aucun commit/push effectué, par consigne) |
| `docs/` absent du dépôt | `docs/MCD.png`, `docs/MCD.jpg`, `docs/MLD.txt` présents dans le dossier de travail (non suivis par Git à ce stade) |
| Palette « provisoire » | Palette du brief appliquée : `#f1f8fc`, `#0074c7`, `#00497c`, `#384050`, `#cd2c2e`, `#82b864` |
| Identifiants DB en clair dans `config/config.php` | Obsolète : configuration lue depuis `.env` (non versionné) |
| `TrajetTest.php` sans `declare(strict_types=1)` | Obsolète : `strict_types=1` présent dans tous les tests |
| Tests exécutés sur la base MariaDB réelle | Obsolète : base **SQLite en mémoire** (`tests/Support/TestDatabase.php`) |
| Outil maison `bin/phpstan-check` = analyse statique | Obsolète : PHPStan 1.12.34 officiel, niveau 6 (`phpstan.neon`), lancé par `bin/phpstan` |
| PHPUnit « 7 tests / 16 assertions » | 11 tests / 25 assertions (dont 4 tests CSRF) |

---

## 1. Périmètre et méthode

L'audit portait sur : le dépôt Git, la sécurité (secrets, CSRF, droits,
sessions), les fonctionnalités (accueil, authentification, trajets, espace
administrateur), le code et les tests, la base de données, l'interface et le
respect du brief, la documentation et les livrables.

Contrôles non destructifs uniquement : lecture des fichiers, commandes Git en
lecture seule, `php -l`, PHPUnit sur base isolée, PHPStan, compilation Sass.
Aucune requête SQL sur la base réelle, aucune exécution de `bin/install.php`,
aucune modification du fichier `.env`.

---

## 2. Points de vigilance encore ouverts

1. **Historique Git** : le commit initial contient encore un ancien mot de
   passe de base de données. La purge de l'historique est une opération
   destructive qui doit faire l'objet d'une décision séparée.
2. **Comptes de démonstration** : mots de passe connus, exigés par le brief
   pour la démonstration ; à changer en production.
3. **Livrable PDF** : le nom de l'étudiant et l'URL du dépôt GitHub restent à
   compléter (champs volontairement laissés en attente, non fabriqués).
4. **Publication** : aucun commit ni push n'a été effectué pendant la mission
   de correction ; le travail de sécurisation est donc encore local.

---

## 3. Commandes de revérification recommandées

```bash
# Syntaxe de tous les fichiers PHP
php -l <fichier.php>

# Tests (base SQLite en mémoire, aucune donnée réelle touchée)
php bin/phpunit

# Analyse statique officielle (niveau 6)
php bin/phpstan

# Compilation Sass vers un fichier temporaire (vérification de validité)
sass --no-source-map public/assets/sass/app.scss /tmp/app.css

# État Git (lecture seule)
git status --porcelain=v1 -uall
```

---

*Document historique — ne pas présenter comme un état des lieux à jour.*
