<?php
/**
 * Gestionnaire de connexion à la base de données (PDO).
 *
 * Exposition d'un accès PDO unique (singleton) vers MySQL / MariaDB.
 * Les accès aux données passent systématiquement par des requêtes préparées
 * afin de se protéger des injections SQL.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

use PDO;
use PDOException;

/**
 * Connexion PDO partagée par l'ensemble de l'application.
 */
final class Database
{
    /** Instance PDO unique (singleton). */
    private static ?PDO $instance = null;

    /** Paramètres de connexion. */
    private array $config;

    /**
     * Constructeur privé (pattern singleton).
     *
     * @param array $config Configuration PDO (dsn, user, password, options).
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Récupère l'instance PDO, en la créant si besoin.
     *
     * @param array|null $config Configuration PDO optionnelle (utilisée une seule fois).
     *
     * @return PDO L'objet PDO partagé.
     */
    public static function pdo(?array $config = null): PDO
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \LogicException('La configuration de la base de données est requise au premier appel.');
            }
            self::$instance = self::connect($config);
        }

        return self::$instance;
    }

    /**
     * Établit effectivement la connexion PDO.
     *
     * @param array $config Configuration (dsn, user, password, options).
     *
     * @return PDO L'objet PDO connecté.
     *
     * @throws PDOException Si la connexion échoue.
     */
    private static function connect(array $config): PDO
    {
        return new PDO(
            $config['dsn'],
            $config['user'],
            $config['password'],
            $config['options'] ?? []
        );
    }

    /**
     * Permet de forcer une connexion pour les tests (ex. base mémoire SQLite).
     *
     * @param PDO $pdo Instance PDO à utiliser.
     */
    public static function setPdo(PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}