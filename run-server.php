<?php
// Fichier de démarrage du serveur PHP intégré (Windows).
// Utilisation : php -S 127.0.0.1:8000 -t public run-server.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Sert les ressources statiques existantes telles quelles.
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Tout le reste passe par le contrôleur frontal.
$path = $uri !== '/' ? $uri : '/';
$_SERVER['REQUEST_URI'] = $path;
require __DIR__ . '/public/index.php';