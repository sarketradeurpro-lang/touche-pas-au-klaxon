<?php
/**
 * Gestion des messages flash.
 *
 * Un message flash est enregistré en session puis affiché une seule fois
 * (à la prochaine requête) avant d'être automatiquement supprimé.
 * Il est utilisé après chaque opération d'écriture puis redirection vers la
 * liste correspondante, comme exigé par le brief.
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

/**
 * Messages éphémères affichés après une redirection.
 */
final class Flash
{
    /** Type de message : succès. */
    public const SUCCESS = 'success';

    /** Type de message : erreur. */
    public const ERROR = 'error';

    /** Type de message : information. */
    public const INFO = 'info';

    /** Clé de stockage en session. */
    private const SESSION_KEY = 'flash_messages';

    /**
     * Ajoute un message flash.
     *
     * @param string $type   Type (Flash::SUCCESS, Flash::ERROR, Flash::INFO).
     * @param string $message Contenu du message.
     */
    public static function add(string $type, string $message): void
    {
        $_SESSION[self::SESSION_KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Récupère tous les messages flash puis les efface.
     *
     * @return array<int, array{type: string, message: string}> Liste des messages.
     */
    public static function pull(): array
    {
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return $messages;
    }

    /**
     * Raccourci pour ajouter un message de succès.
     *
     * @param string $message Contenu du message.
     */
    public static function success(string $message): void
    {
        self::add(self::SUCCESS, $message);
    }

    /**
     * Raccourci pour ajouter un message d'erreur.
     *
     * @param string $message Contenu du message.
     */
    public static function error(string $message): void
    {
        self::add(self::ERROR, $message);
    }

    /**
     * Raccourci pour ajouter un message d'information.
     *
     * @param string $message Contenu du message.
     */
    public static function info(string $message): void
    {
        self::add(self::INFO, $message);
    }
}