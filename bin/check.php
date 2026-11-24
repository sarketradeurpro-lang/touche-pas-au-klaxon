<?php
/**
 * Diagnostic d'environnement – connexion DB via variables d'environnement.
 *
 * Même liste blanche que config/config.php : seules DB_DSN, DB_USER et
 * DB_PASSWORD sont lues depuis .env. Toutes les autres clés sont ignorées.
 *
 * Le journal ne contient que des compteurs et des codes d'erreur génériques :
 * aucune donnée personnelle, aucun mot de passe, aucun DSN, aucun message
 * d'exception et aucune trace.
 */

declare(strict_types=1);

$log = __DIR__ . '/../check.log';
$lines = [];

/**
 * Écrit le journal après obtention du verrou, sans troncature préalable.
 *
 * @param string             $path  Chemin du journal.
 * @param array<int, string> $lines Lignes à écrire.
 *
 * @return bool Vrai si l'écriture complète a réussi.
 */
$writeLog = static function (string $path, array $lines): bool {
    $payload = implode("\n", $lines) . "\n";

    // Création avec umask restrictif : ce masque est un mécanisme POSIX.
    // Sous Windows, umask(0077) ne constitue PAS une garantie de protection des
    // ACL NTFS : le fichier hérite des ACL du dossier parent. Le mode 'c+' ouvre
    // sans tronquer : le contenu n'est modifié qu'après obtention du verrou.
    $previousUmask = umask(0077);
    $handle = @fopen($path, 'c+b');
    umask($previousUmask);

    if ($handle === false) {
        return false;
    }

    if (!@flock($handle, LOCK_EX)) {
        @fclose($handle);
        return false;
    }

    $written = @ftruncate($handle, 0)
        && @rewind($handle)
        && @fwrite($handle, $payload) === strlen($payload)
        && @fflush($handle);

    // LOCK_UN n'est pas déterminant : la documentation flock() précise que le
    // verrou est libéré par fclose() (ou à la destruction du flux). L'appel
    // reste neutralisé et n'est suivi d'aucune écriture.
    @flock($handle, LOCK_UN);

    // Dernière opération sur le flux : seul signal fiable de finalisation.
    // Aucune écriture ne doit être tentée après fclose().
    $closed = @fclose($handle);

    if (!$written || !$closed) {
        return false;
    }

    // Meilleur effort uniquement : sur Windows, chmod() ne modifie que
    // l'attribut « lecture seule » et ne restreint PAS les ACL NTFS.
    // Durcissement Windows : dépend des ACL du dossier parent, héritées à la
    // création. Aucune commande ACL n'est fournie ici : le compte réel du
    // processus PHP doit d'abord être identifié (le compte de session n'est pas
    // nécessairement celui du service ou de la tâche planifiée).
    @chmod($path, 0600);

    return true;
};

/**
 * Termine le script sur une erreur de configuration, sans rien divulguer.
 *
 * @return never
 */
$failConfig = static function () use ($writeLog, $log): never {
    if ($writeLog($log, ['ERROR Configuration database failed'])) {
        fwrite(STDERR, "ERROR Configuration database failed\n");
        exit(1);
    }

    fwrite(STDERR, "ERROR Diagnostic log unavailable\n");
    exit(1);
};

// --- Chargement du .env (liste blanche identique à config/config.php) ------
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    if (!is_readable($envPath)) {
        $failConfig();
    }

    $linesEnv = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($linesEnv === false) {
        $failConfig();
    }

    $allowed = ['DB_DSN', 'DB_USER', 'DB_PASSWORD'];

    foreach ($linesEnv as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Toute clé hors liste blanche est ignorée (DB_DSN, DB_USER, DB_PASSWORD).
        if (!in_array($key, $allowed, true)) {
            continue;
        }

        // Priorité aux variables déjà définies par le serveur/système.
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// --- Configuration obligatoire -------------------------------------------
$dsn = getenv('DB_DSN');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

if (
    $dsn === false || $user === false || $password === false
    || $dsn === '' || $user === '' || $password === ''
) {
    $failConfig();
}

// --- Diagnostic : trois comptages uniquement ------------------------------
$exitCode = 0;

try {
    $db = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ag = (int) $db->query('SELECT COUNT(*) FROM agences')->fetchColumn();
    $em = (int) $db->query('SELECT COUNT(*) FROM employes')->fetchColumn();
    $tr = (int) $db->query('SELECT COUNT(*) FROM trajets')->fetchColumn();
    $lines[] = "DB_OK agences=$ag employes=$em trajets=$tr";
} catch (PDOException) {
    // Message générique : ni détail SQL, ni message du pilote, ni trace.
    $lines[] = 'ERROR Diagnostic database failed';
    $exitCode = 2;
}

// --- Écriture du journal --------------------------------------------------
// Journal situé à la racine du projet, en dehors de public/ : il n'est donc
// jamais servi par le serveur web.
if (!$writeLog($log, $lines)) {
    fwrite(STDERR, "ERROR Diagnostic log unavailable\n");
    exit(1);
}

exit($exitCode);
