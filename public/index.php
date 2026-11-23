<?php
/**
 * Point d'entrée de l'application « Touche pas au klaxon ».
 *
 * Ce fichier :
 *   1. charge le fichier de configuration et l'autoloader ;
 *   2. démarre la session ;
 *   3. construit le routeur et déclare l'ensemble des routes ;
 *   4. distribue la requête entrante.
 *
 * Destiné à être appelé via le serveur de développement PHP :
 *     php -S 127.0.0.1:8000 -t public public/index.php
 * ou en production via un serveur web (Apache/WAMP) pointant sur public/.
 *
 * @package TouchePasAuKlaxon
 */

declare(strict_types=1);

use TouchePasAuKlaxon\Core\Router;
use TouchePasAuKlaxon\Core\Request;

// --- Configuration et autoload --------------------------------------------
$config = require __DIR__ . '/../config/config.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Mode hors-ligne : autoloader maison.
    require __DIR__ . '/../autoload.php';
}

// --- Environnement --------------------------------------------------------
if (($config['env'] ?? 'dev') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// --- Session --------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name'] ?? 'tpfk_session');

    // Tableau de sessions local au projet (indépendant du répertoire tmp du
    // serveur), créé à la volée s'il n'existe pas.
    $sessionDir = dirname(__DIR__) . '/var/sessions';
    if (is_dir($sessionDir) || @mkdir($sessionDir, 0775, true)) {
        session_save_path($sessionDir);
    }

    session_start();
}

// --- Routage --------------------------------------------------------------
$router = new Router($config);
$request = Request::fromGlobals();

$ns = 'TouchePasAuKlaxon\\Controllers\\';

// Page d'accueil (liste des trajets avec places disponibles).
$router->get('/', $ns . 'HomeController@index');
$router->get('/trajets', $ns . 'HomeController@index');

// Authentification.
$router->get('/login', $ns . 'AuthController@showLogin');
$router->post('/login', $ns . 'AuthController@login');
$router->get('/logout', $ns . 'AuthController@logout');

// Trajets : création, consultation, modification, suppression.
$router->get('/trajets/creer', $ns . 'TrajetController@create');
$router->post('/trajets/creer', $ns . 'TrajetController@store');
$router->get('/trajets/{id}', $ns . 'TrajetController@show');
$router->get('/trajets/{id}/modifier', $ns . 'TrajetController@edit');
$router->post('/trajets/{id}/modifier', $ns . 'TrajetController@update');
$router->post('/trajets/{id}/supprimer', $ns . 'TrajetController@destroy');

// Espace administrateur.
$router->get('/admin', $ns . 'AdminController@dashboard');
$router->get('/admin/agences', $ns . 'AgenceController@index');
$router->get('/admin/agences/creer', $ns . 'AgenceController@create');
$router->post('/admin/agences/creer', $ns . 'AgenceController@store');
$router->get('/admin/agences/{id}/modifier', $ns . 'AgenceController@edit');
$router->post('/admin/agences/{id}/modifier', $ns . 'AgenceController@update');
$router->post('/admin/agences/{id}/supprimer', $ns . 'AgenceController@destroy');
$router->get('/admin/employes', $ns . 'AdminController@employees');

// --- Dispatch -------------------------------------------------------------
$router->dispatch($request);