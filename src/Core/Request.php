<?php
/**
 * Représentation de la requête HTTP entrante.
 *
 * Encapsule la méthode HTTP, les paramètres (GET/POST) et la partie « chemin »
 * de l'URL afin d'alimenter le routeur.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

/**
 * Requête HTTP entrante.
 */
final class Request
{
    private string $method;

    private string $path;

    /** @var array<string, string> Paramètres de route (placeholders). */
    private array $routeParams = [];

    /**
     * Constructeur.
     *
     * @param string $method Méthode HTTP (GET, POST, ...).
     * @param string $path   Chemin de l'URL (sans serveur ni query string).
     */
    public function __construct(string $method, string $path)
    {
        $this->method = strtoupper($method);
        $this->path   = $path;
    }

    /**
     * Construit une requête à partir des superglobales.
     *
     * @return Request Requête courante.
     */
    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Supprime la query string.
        $path = (string) parse_url($uri, PHP_URL_PATH);

        return new self($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
    }

    /**
     * Méthode HTTP de la requête.
     *
     * @return string GET, POST, PUT, DELETE...
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Chemin de la requête.
     *
     * @return string Chemin (ex. "/trajets/5").
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Paramètres GET.
     *
     * @param string     $key     Nom du paramètre.
     * @param mixed|null $default Valeur par défaut.
     *
     * @return mixed Valeur trouvée ou défaut.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Paramètres POST.
     *
     * @param string     $key     Nom du paramètre.
     * @param mixed|null $default Valeur par défaut.
     *
     * @return mixed Valeur trouvée ou défaut.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Indique si la requête est une soumission POST.
     *
     * @return bool Vrai si POST.
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Enregistre un paramètre de route capturé par le routeur.
     *
     * @param string $key   Nom du placeholder.
     * @param string $value Valeur capturée.
     */
    public function setRouteParam(string $key, string $value): void
    {
        $this->routeParams[$key] = $value;
    }

    /**
     * Récupère un paramètre de route.
     *
     * @param string $key Nom du paramètre.
     *
     * @return string|null Valeur ou null.
     */
    public function routeParam(string $key): ?string
    {
        return $this->routeParams[$key] ?? null;
    }
}