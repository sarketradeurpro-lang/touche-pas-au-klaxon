<?php
/**
 * Routeur HTTP minimaliste.
 *
 * Inspiré de la bibliothèque « izniburak/router » (PSR-4, MIT), ce routeur
 * associe une méthode HTTP + un motif d'URL à une action. Il capture les
 * placeholders nommés ({id}, {param}) et les injecte dans la requête.
 *
 * En environnement normal, le fichier composer.json référence la
 * bibliothèque officielle izniburak/router ; cette implémentation maison
 * (auto-suffisante, sans dépendance) remplit la **même** mission : un routeur
 * PHP à la racine de l'application.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

/**
 * Routeur HTTP.
 */
final class Router
{
    /**
     * Table des routes : [méthode][motif_regex] => [pattern, action].
     *
     * @var array<string, array<string, array{pattern: string, action: callable|string}>>
     */
    private array $routes = [];

    /**
     * Configuration globale de l'application.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Constructeur.
     *
     * @param array<string, mixed> $config Configuration globale (base de données, chemins...).
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        // Enregistre la configuration PDO sans ouvrir de connexion : les modèles
        // se connectent à la première requête (connexion paresseuse).
        if (isset($config['db'])) {
            Database::configure($config['db']);
        }
    }

    /**
     * Enregistre une route GET.
     *
     * @param string          $pattern Motif d'URL (ex. "/trajets/{id}").
     * @param callable|string $action  Callable, ou "Controller@methode".
     */
    public function get(string $pattern, callable|string $action): void
    {
        $this->add('GET', $pattern, $action);
    }

    /**
     * Enregistre une route POST.
     *
     * @param string          $pattern Motif d'URL.
     * @param callable|string $action  Callable, ou "Controller@methode".
     */
    public function post(string $pattern, callable|string $action): void
    {
        $this->add('POST', $pattern, $action);
    }

    /**
     * Enregistre une route pour n'importe quelle méthode HTTP.
     *
     * @param string          $pattern Motif d'URL.
     * @param callable|string $action  Callable, ou "Controller@methode".
     */
    public function any(string $pattern, callable|string $action): void
    {
        $this->add('ANY', $pattern, $action);
    }

    /**
     * Ajoute une route dans la table interne.
     *
     * @param string          $method  Méthode HTTP ou ANY.
     * @param string          $pattern Motif d'URL.
     * @param callable|string $action  Action associée.
     */
    private function add(string $method, string $pattern, callable|string $action): void
    {
        $regex = $this->patternToRegex($pattern);
        $this->routes[$method][$regex] = ['pattern' => $pattern, 'action' => $action];
    }

    /**
     * Convertit un motif d'URL en expression régulière tout en collectant le
     * nom des placeholders.
     *
     * @param string $pattern Motif (ex. "/trajets/{id}").
     *
     * @return string Expression régulière matcher la route.
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );

        return '#^' . $regex . '$#';
    }

    /**
     * Résout la requête et exécute l'action correspondante.
     *
     * @param Request $request Requête entrante.
     */
    public function dispatch(Request $request): void
    {
        $method   = $request->method();
        $possible = [($this->routes[$method] ?? []), ($this->routes['ANY'] ?? [])];

        foreach ($possible as $matched) {
            foreach ($matched as $regex => $route) {
                if (preg_match($regex, $request->path(), $params) === 1) {
                    foreach ($params as $key => $value) {
                        if (is_string($key)) {
                            $request->setRouteParam($key, $value);
                        }
                    }
                    $this->run($route['action'], $request);

                    return;
                }
            }
        }

        // Aucune route ne correspond : 404.
        http_response_code(404);
        echo '<h1>404 — Page introuvable</h1>';
    }

    /**
     * Exécute l'action : callable direct ou "Controller@methode".
     *
     * @param callable|string $action  Action à exécuter.
     * @param Request         $request Requête entrante.
     */
    private function run(callable|string $action, Request $request): void
    {
        if (is_callable($action)) {
            ($action)($request);

            return;
        }

        // Format "Namespace\Controller@methode".
        $parts = explode('@', $action, 2);
        $class = $parts[0];
        $method = $parts[1] ?? 'index';
        $controller = new $class($this->config);
        $controller->$method($request);
    }
}