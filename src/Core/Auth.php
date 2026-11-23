<?php
/**
 * Gestion de l'authentification et des autorisations.
 *
 * Fournit les utilitaires de connexion / déconnexion, la lecture de
 * l'employé connecté et la vérification des rôles.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

use TouchePasAuKlaxon\Models\Employe;

/**
 * Session de l'utilisateur courant.
 */
final class Auth
{
    /** Clé de session contenant l'id de l'employé connecté. */
    private const SESSION_USER_ID = 'user_id';

    /** Rôle administrateur. */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /** Rôle utilisateur standard. */
    public const ROLE_USER = 'ROLE_USER';

    /**
     * Connecte un employé dans la session.
     *
     * @param int $employeId Identifiant de l'employé.
     */
    public static function login(int $employeId): void
    {
        $_SESSION[self::SESSION_USER_ID] = $employeId;
    }

    /**
     * Déconnecte l'utilisateur courant.
     */
    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_ID]);
    }

    /**
     * Indique si un utilisateur est connecté.
     *
     * @return bool Vrai si connecté.
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }

    /**
     * Récupère l'employé connecté (ou null si non connecté).
     *
     * @return Employe|null Employé courant.
     */
    public static function user(): ?Employe
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return (new Employe())->find((int) $_SESSION[self::SESSION_USER_ID]);
    }

    /**
     * Indique si l'utilisateur connecté est administrateur.
     *
     * @return bool Vrai si administrateur.
     */
    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user->getRole() === self::ROLE_ADMIN;
    }

    /**
     * Redirige vers la page de connexion si aucun utilisateur n'est connecté.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Redirige vers l'accueil si l'utilisateur n'est pas administrateur.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /');
            exit;
        }
    }
}