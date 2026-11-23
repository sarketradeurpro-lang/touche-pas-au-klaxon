<?php
/** Diagnostic d'environnement : écrit ses résultats dans check.log. */
declare(strict_types=1);
$log = __DIR__ . '/../check.log';
$lines = [];
try {
    $db = new PDO(
        'mysql:host=127.0.0.1;port=3310;dbname=covoiturage;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $ag = (int) $db->query('SELECT COUNT(*) FROM agences')->fetchColumn();
    $em = (int) $db->query('SELECT COUNT(*) FROM employes')->fetchColumn();
    $tr = (int) $db->query('SELECT COUNT(*) FROM trajets')->fetchColumn();
    $lines[] = "DB_OK agences=$ag employes=$em trajets=$tr";
    $st = $db->prepare('SELECT email, role FROM employes WHERE role = :r');
    $st->execute([':r' => 'ROLE_ADMIN']);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $lines[] = 'ADMIN ' . $row['email'] . ' role=' . $row['role'];
    }
} catch (Throwable $ex) {
    $lines[] = 'ERROR ' . $ex->getMessage();
    $lines[] = 'TRACE ' . $ex->getTraceAsString();
}
file_put_contents($log, implode("\n", $lines) . "\n");