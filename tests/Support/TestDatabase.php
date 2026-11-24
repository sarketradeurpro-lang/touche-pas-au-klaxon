<?php
/**
 * Base de données de test isolée (SQLite en mémoire).
 *
 * Aucun test ne doit toucher la base MariaDB réelle : chaque test construit
 * ici un schéma minimal compatible avec les requêtes des modèles, puis
 * l'injecte dans le singleton via Database::setPdo().
 *
 * @package TouchePasAuKlaxon\Tests\Support
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Tests\Support;

use PDO;

/**
 * Fabrique une base SQLite en mémoire pré-remplie.
 */
final class TestDatabase
{
    /** Email de l'administrateur fictif utilisé par les tests. */
    public const ADMIN_EMAIL = 'admin.test@example.test';

    /** Mot de passe de test (jamais utilisé en production). */
    public const ADMIN_PASSWORD = 'Test-Password-123!';

    /**
     * Crée et alimente une base de test isolée.
     *
     * @return PDO Connexion SQLite en mémoire.
     */
    public static function create(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec(
            'CREATE TABLE agences (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                ville TEXT NOT NULL UNIQUE
            )'
        );

        $pdo->exec(
            "CREATE TABLE employes (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                nom          TEXT NOT NULL,
                prenom       TEXT NOT NULL,
                email        TEXT NOT NULL UNIQUE,
                telephone    TEXT NULL,
                role         TEXT NOT NULL DEFAULT 'ROLE_USER',
                mot_de_passe TEXT NOT NULL
            )"
        );

        $pdo->exec(
            'CREATE TABLE trajets (
                id                    INTEGER PRIMARY KEY AUTOINCREMENT,
                auteur_id             INTEGER NOT NULL REFERENCES employes (id),
                agence_depart_id      INTEGER NOT NULL REFERENCES agences (id),
                agence_arrivee_id     INTEGER NOT NULL REFERENCES agences (id),
                date_depart           TEXT NOT NULL,
                date_arrivee          TEXT NOT NULL,
                nb_places_total       INTEGER NOT NULL,
                nb_places_disponibles INTEGER NOT NULL,
                CHECK (agence_depart_id <> agence_arrivee_id),
                CHECK (date_arrivee > date_depart),
                CHECK (nb_places_total > 0),
                CHECK (nb_places_disponibles >= 0 AND nb_places_disponibles <= nb_places_total)
            )'
        );

        $agenceStmt = $pdo->prepare('INSERT INTO agences (ville) VALUES (:ville)');
        foreach (['Paris', 'Lyon', 'Marseille'] as $ville) {
            $agenceStmt->execute([':ville' => $ville]);
        }

        $pdo->prepare(
            'INSERT INTO employes (nom, prenom, email, telephone, role, mot_de_passe)
             VALUES (:nom, :prenom, :email, :telephone, :role, :mot_de_passe)'
        )->execute([
            ':nom'          => 'Test',
            ':prenom'       => 'Admin',
            ':email'        => self::ADMIN_EMAIL,
            ':telephone'    => '0000000000',
            ':role'         => 'ROLE_ADMIN',
            ':mot_de_passe' => password_hash(self::ADMIN_PASSWORD, PASSWORD_DEFAULT),
        ]);

        return $pdo;
    }
}
