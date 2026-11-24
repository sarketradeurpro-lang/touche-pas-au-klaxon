<?php
/**
 * Configuration globale – chargement des variables d’environnement.
 *
 * Le fichier .env (non versionné) doit être placé à la racine du projet.
 * Exemple : .env.example → .env
 *
 * @package TouchePasAuKlaxon\Config
 */

declare(strict_types=1);

/* -------------------------------------------------------------
 * Chargement manuel du fichier .env (sans dépendance externe)
 * ------------------------------------------------------------- */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    if (!is_readable($envPath)) {
        throw new RuntimeException('Impossible de lire le fichier .env à la racine du projet.');
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $allowed = ['DB_DSN', 'DB_USER', 'DB_PASSWORD'];
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorer les commentaires et lignes vides
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue; // ligne mal formée, on l'ignore
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if (!in_array($key, $allowed, true)) {
            continue; // clé non autorisée, on l'ignore
        }
        // Priorité aux variables déjà définies par le serveur/système
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/* -------------------------------------------------------------
 * Vérification des variables obligatoires
 * ------------------------------------------------------------- */
$required = ['DB_DSN', 'DB_USER', 'DB_PASSWORD'];
foreach ($required as $var) {
    $val = getenv($var);
    if ($val === false || $val === '') {
        throw new RuntimeException("Configuration DB manquante ou vide – la variable d'environnement {$var} doit être définie et non vide.");
    }
}

return [
    // --- Base de données ---------------------------------------------------
    'db' => [
        'dsn'      => getenv('DB_DSN'),
        'user'     => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
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
        'root'   => dirname(__DIR__),
        'views'  => dirname(__DIR__) . '/src/Views',
        'public' => dirname(__DIR__) . '/public',
        'config' => __DIR__,
    ],

    // --- Session (inchangé) -------------------------------------------------
    'session_name' => 'tpfk_session',
    'session'      => [
        'cookie_lifetime' => 0,
        'cookie_path'     => '/',
        'cookie_domain'   => '',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => null,
    ],
];
