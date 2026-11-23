<?php
/**
 * Autoloader PSR-4 « de secours ».
 *
 * L'autoloader officiel est celui généré par Composer (vendor/autoload.php).
 * Ce fichier permet à l'application de fonctionner **sans** Composer
 * (environnement hors-ligne), en chargeant simplement les classes du projet
 * selon leurs espaces de noms.
 *
 * Utilisation : `require __DIR__ . '/autoload.php';`
 *
 * @package TouchePasAuKlaxon
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    // Seules les classes de notre application sont gérées ici.
    $prefix = 'TouchePasAuKlaxon\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});