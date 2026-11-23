<?php
/**
 * Contrôleur de base.
 *
 * Factorise les helpers communs à tous les contrôleurs : accès PDO,
 * rendu de vues et redirections.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

use PDO;

/**
 * Classe mère de tous les contrôleurs.
 */
abstract class Controller
{
    private array $config;

    private View $view;

    /**
     * Constructeur : récupère la configuration et prépare le moteur de vues.
     *
     * @param array $config Configuration globale de l'application.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->view = new View($config['paths']['views']);
    }

    /**
     * Accès à la connexion PDO partagée.
     *
     * @return PDO Connexion.
     */
    protected function db(): PDO
    {
        return Database::pdo($this->config['db']);
    }

    /**
     * Rendu d'un gabarit dans le layout global.
     *
     * @param string $view Nom de la vue.
     * @param array  $data Variables injectées.
     *
     * @return string HTML final (déjà envoyé).
     */
    protected function render(string $view, array $data = []): string
    {
        $html = $this->view->renderWithLayout($view, 'layout', $data);
        echo $html;

        return $html;
    }

    /**
     * Redirige vers une URL relative puis termine le script.
     *
     * @param string $url Chemin interne (ex. "/admin/agences").
     *
     * @return never
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}