<?php
/**
 * Protection CSRF (Cross-Site Request Forgery).
 *
 * Fournit un jeton aléatoire stocké en session, un champ caché prêt à insérer
 * dans les formulaires et une vérification en temps constant (hash_equals).
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

/**
 * Gestion du jeton CSRF de la session courante.
 */
final class Csrf
{
    /** Clé de session contenant le jeton. */
    private const SESSION_KEY = 'csrf_token';

    /**
     * Retourne le jeton de la session, en le créant au besoin.
     *
     * @return string Jeton hexadécimal (64 caractères).
     */
    public static function token(): string
    {
        $current = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($current) || $current === '') {
            $current = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $current;
        }

        return $current;
    }

    /**
     * Alias historique utilisé par les contrôleurs.
     *
     * @return string Jeton de la session.
     */
    public static function generate(): string
    {
        return self::token();
    }

    /**
     * Champ caché HTML à insérer dans un formulaire.
     *
     * @return string Balise input échappée.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie un jeton soumis.
     *
     * @param mixed $token Valeur reçue en POST.
     *
     * @return bool Vrai si le jeton correspond à celui de la session.
     */
    public static function validate(mixed $token): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
