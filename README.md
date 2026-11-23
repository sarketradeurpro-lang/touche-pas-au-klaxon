# Touche pas au klaxon

Application web **MVC en PHP** de covoiturage entre les agences d'une entreprise.
Les employés sont importés de manière **lecture seule** depuis un fichier
(`database/users.txt`) ; les agences et les trajets sont gérés par l'application.
Un rôle distinct **Administrateur** (ROLE_ADMIN) permet la gestion des agences.

## Stack

| Composant | Version |
|-----------|---------|
| PHP       | 8.1+ (développement sous 8.3.28) |
| Serveur   | MariaDB 10.11+ (développement sous 11.4.9) |
| Front     | HTML5 + CSS3 (palette centralisée, principe inspiré de Sass) |
| Connexion | PDO MySQLi avec requêtes préparées (`PDO::ATTR_EMULATE_PREPARES=false`) |

## Architecture

Le projet suit une architecture **MVC maison** : un routeur auto-hébergé
(`src/Core/Router.php`) route chaque requête vers un contrôleur, les modèles
(`src/Models`) accèdent aux données via PDO, et les vues (`src/Views`) rendent
le HTML.

```
public/index.php      Front controller (routage + session)
src/Core/             Router, Controller, Database, Request, Auth, Flash, View
src/Models/           Employe, Agence, Trajet
src/Controllers/      Home, Auth, Trajet, Agence, Admin
src/Views/            gabarits et vues (layout.php, trajets/, auth/, admin/)
database/             schema.sql, agences.txt, users.txt
bin/                  install.php, check.php, phpunit, phpstan-check
tools/                makepdf.py (génération du rapport PDF)
```

## Installation et lancement

Prérequis : WAMP avec **PHP 8.1+** et **MariaDB** actifs.

```bash
cd tpfk
php bin/install.php                          # crée la base + données de démo
php -S 127.0.0.1:8000 run-server.php         # serveur de développement
# puis ouvrez http://127.0.0.1:8000
```

L'application fonctionne **sans Composer** : un `autoload.php` maison et des
runners (`bin/phpunit`, `bin/phpstan-check`) sont fournis. Si `vendor/autoload.php`
existe (Composer installé), les outils réels sont utilisés en priorité.

### Configuration de la base

Les identifiants sont centralisés dans `config/config.php` :

| Paramètre | Valeur |
|-----------|--------|
| Hôte       | `127.0.0.1` |
| Port       | `3310` |
| Base       | `covoiturage` |
| Utilisateur| `covoiturage` |
| Mot de passe | `Covoiturage_2024!` |

> Par défaut le port `3306` peut être utilisé ; ajustez `dsn` dans
> `config/config.php` si votre MariaDB écoute sur un autre port.

## Comptes de connexion (démo)

| Rôle        | E-mail                     | Mot de passe |
|-------------|----------------------------|--------------|
| Employé     | `maxime.petit@email.fr`    | `Passw0rd!`  |
| Administrateur | `alexandre.martin@email.fr` | `Admin_2024!` |

Les mots de passe sont stockés hachés (`password_hash`) et vérifiés par
`password_verify`. Les rôles sont gérés par la colonne `role`.

## Validation métier des trajets

- L'agence **d'arrivée doit être différente** de l'agence de départ.
- La **date de départ** (et l'heure) doit être **postérieure** à la date de
  départ du trajet (comparaison d'horodatages, colonnes `DATETIME`).

## Outils

```bash
php bin/phpunit                    # runner de tests compatible PHPUnit
php bin/phpstan-check --level=1    # analyseur statique maison (type PHPStan)
php bin/check.php                  # diagnostic de santé (DB, sessions, PHP)
```

## Génération du rapport PDF

```bash
python tools/makepdf.py tools/content.json Nom_Prenom_Titre_devoir.pdf
```

Script **indépendant** (bibliothèque standard uniquement), qui produit le
rapport de livraison `Nom_Prenom_Titre_devoir.pdf` dans le répertoire racine.

## Palette / thème

La palette est centralisée dans `:root` via des variables CSS `--tpk-*`.
Les couleurs par défaut (bleu Bootstrap) sont utilisées tant que les valeurs
finales de la charte ne sont pas fournies.