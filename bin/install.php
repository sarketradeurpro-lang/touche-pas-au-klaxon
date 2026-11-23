<?php
/**
 * Script d'installation / réinitialisation de la base de données.
 *
 * Ce script :
 *   1. exécute database/schema.sql (création de la base + des tables +
 *      de l'utilisateur applicatif) ;
 *   2. insère les 12 agences du fichier agences.txt ;
 *   3. insère les 20 employés du fichier users.txt (mot de passe par défaut) ;
 *   4. désigne un compte administrateur ;
 *   5. crée quelques trajets de démonstration.
 *
 * Utilisation (à la racine du dépôt) :
 *     php bin/install.php
 *
 * @package TouchePasAuKlaxon\Bin
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use TouchePasAuKlaxon\Core\Database;
use TouchePasAuKlaxon\Core\Flash;

// --- Paramètres de connexion root (pour créer la base) ---------------------
$rootHost = $argv[1] ?? '127.0.0.1';
$rootPort = (int) ($argv[2] ?? 3310);
$rootUser = $argv[3] ?? 'root';
$rootPass = $argv[4] ?? 'root';

$rootPdo = new PDO(
    "mysql:host=$rootHost;port=$rootPort;charset=utf8mb4",
    $rootUser,
    $rootPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cli = static fn (string $msg) => printf("[install] %s\n", $msg);

// --- 1) Schéma --------------------------------------------------------------
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Impossible de lire database/schema.sql\n");
    exit(1);
}
$cli('Exécution du schéma (création base + tables)...');
$rootPdo->exec($schema);
$cli('Schéma appliqué.');

// --- 2 & 3) Données initiales (agences + employés) -------------------------
$dbConfig = [
    'dsn'      => "mysql:host=$rootHost;port=$rootPort;dbname=covoiturage;charset=utf8mb4",
    'user'     => 'covoiturage',
    'password' => 'Covoiturage_2024!',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
Database::pdo($dbConfig);
$db = Database::pdo();

$cli('Insertion des agences...');
$agences = array_values(array_filter(array_map('trim', file(
    __DIR__ . '/../database/agences.txt',
    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
) ?? [])));
$agenceStmt = $db->prepare('INSERT INTO agences (ville) VALUES (:ville)');
foreach ($agences as $ville) {
    if ($ville !== '') {
        $agenceStmt->execute([':ville' => $ville]);
    }
}
$cli(count($agences) . " agences insérées.");

$cli('Insertion des employés...');
$defaultPassword = 'Passw0rd!';
$hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
$adminPassword = 'Admin_2024!';
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

$empStmt = $db->prepare(
    'INSERT INTO employes (nom, prenom, email, telephone, role, mot_de_passe)
     VALUES (:nom, :prenom, :email, :telephone, :role, :mot_de_passe)'
);

$lines = file(__DIR__ . '/../database/users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?? [];
$adminEmail = null;
foreach ($lines as $line) {
    [$nom, $prenom, $telephone, $email] = array_map('trim', array_pad(explode(',', $line), 4, ''));
    if ($nom === '' || $email === '') {
        continue;
    }
    // Désigne « Alexandre Martin » comme administrateur de démonstration.
    $role = (mb_strtolower($prenom) === 'alexandre' && mb_strtolower($nom) === 'martin')
        ? 'ROLE_ADMIN'
        : 'ROLE_USER';
    $pwd = $role === 'ROLE_ADMIN' ? $adminHash : $hash;

    $empStmt->execute([
        ':nom'             => $nom,
        ':prenom'          => $prenom,
        ':email'           => $email,
        ':telephone'       => $telephone !== '' ? $telephone : null,
        ':role'            => $role,
        ':mot_de_passe'    => $pwd,
    ]);

    if ($role === 'ROLE_ADMIN') {
        $adminEmail = $email;
    }
}
$cli((count(array_filter($lines))) . " employés insérés.");

// --- 5) Trajets de démonstration -------------------------------------------
$cli('Insertion de trajets de démonstration...');
$trajetStmt = $db->prepare(
    'INSERT INTO trajets
        (auteur_id, agence_depart_id, agence_arrivee_id, date_depart, date_arrivee,
         nb_places_total, nb_places_disponibles)
     VALUES
        (:auteur, :dep, :arr, :dd, :da, :total, :dispo)'
);

// Récupère les ids par ville.
$idByVille = [];
foreach ($db->query('SELECT id, ville FROM agences')->fetchAll() as $row) {
    $idByVille[$row['ville']] = (int) $row['id'];
}
// Récupère quelques ids employés.
$employeIds = array_column($db->query('SELECT id FROM employes ORDER BY id LIMIT 6')->fetchAll(), 'id');

$demo = [
    ['Paris', 'Lyon', '+2 days 08:00', '+2 days 10:00', 4, 3],
    ['Lyon', 'Marseille', '+1 day 09:00', '+1 day 12:00', 3, 2],
    ['Nantes', 'Bordeaux', '+3 days 07:30', '+3 days 09:30', 4, 4],
    ['Paris', 'Marseille', '+5 days 06:00', '+5 days 12:00', 5, 1],
    ['Lille', 'Paris', '+2 days 06:30', '+2 days 08:00', 4, 2],
    ['Toulouse', 'Montpellier', '+1 day 08:30', '+1 day 10:00', 3, 3],
];

foreach ($demo as $i => $t) {
    [$dep, $arr, $dd, $da, $total, $dispo] = $t;
    if (!isset($idByVille[$dep], $idByVille[$arr])) {
        continue;
    }
    $trajetStmt->execute([
        ':auteur' => $employeIds[$i % count($employeIds)],
        ':dep'    => $idByVille[$dep],
        ':arr'    => $idByVille[$arr],
        ':dd'     => (new DateTime($dd))->format('Y-m-d H:i:s'),
        ':da'     => (new DateTime($da))->format('Y-m-d H:i:s'),
        ':total'  => $total,
        ':dispo'  => $dispo,
    ]);
}

$cli("Installation terminée avec succès.");
echo "--------------------------------------------------------------\n";
echo "  Compte ADMIN    : $adminEmail / $adminPassword\n";
echo "  Compte EMPLOYÉ  : ex. sophie.dubois@email.fr / $defaultPassword\n";
echo "  (Tous les autres employés : mot de passe $defaultPassword)\n";
echo "--------------------------------------------------------------\n";