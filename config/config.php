<?php
/**
 * Configuration globale de l'application « Touche pas au klaxon ».
 *
 * Ce fichier centralise :
 *   - la configuration de connexion à la base de données (PDO) ;
 *   - l'état de l'environnement (affichage des erreurs) ;
 *   - les chemins de base du projet (racine, dossier public).
 *
 * @package TouchePasAuKlaxon\Config
 */

declare(strict_types=1);

return [
    // --- Base de données ---------------------------------------------------
    'db' => [
        'dsn'      => 'mysql:host=127.0.0.1;port=3310;dbname=covoiturage;charset=utf8mb4',
        'user'     => 'covoiturage',
        'password' => 'Covoiturage_2024!',
        // Options PDO recommandées pour la sécurité des requêtes préparées.
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // --- Environnement -----------------------------------------------------
    'env' => 'dev', // 'dev' | 'prod'

    // --- Chemins -----------------------------------------------------------
    'paths' => [
        'root'      => dirname(__DIR__),
        'views'     => dirname(__DIR__) . '/src/Views',
        'public'    => dirname(__DIR__) . '/public',
        'config'    => __DIR__,
    ],

    // --- Session -----------------------------------------------------------
    'session_name' => 'tpfk_session',
];