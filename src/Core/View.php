<?php
/**
 * Moteur de vues.
 *
 * Charge un fichier de vue (HTML/PHP) et lui injecte des variables.
 * Les vues sont stockées dans src/Views et peuvent comporter une mise en
 * page commune (header / footer).
 *
 * @package TouchePasAuKlaxon\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Core;

/**
 * Rendu des gabarits HTML.
 */
final class View
{
    private string $viewsPath;

    /**
     * Constructeur.
     *
     * @param string $viewsPath Chemin absolu vers le dossier des vues.
     */
    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, '/\\');
    }

    /**
     * Rend une vue avec ses variables dans un tampon de sortie, puis renvoie
     * le HTML produit.
     *
     * @param string $template Nom du gabarit (sans .php).
     * @param array  $data     Variables injectées dans la vue.
     *
     * @return string HTML généré.
     */
    public function render(string $template, array $data = []): string
    {
        // Extrait les clés du tableau sous forme de variables locales.
        extract($data, EXTR_SKIP);

        ob_start();
        include $this->viewsPath . '/' . $template . '.php';
        $content = (string) ob_get_clean();

        return $content;
    }

    /**
     * Rendu d'une vue dans son gabarit global (layout + contenu).
     *
     * @param string $template Nom du gabarit de contenu.
     * @param string $layout   Nom du layout (gabarit global).
     * @param array  $data     Variables injectées (dont $content).
     *
     * @return string HTML final.
     */
    public function renderWithLayout(string $template, string $layout = 'layout', array $data = []): string
    {
        $data['content'] = $this->render($template, $data);
        $data['flashMessages'] = Flash::pull();
        $data['currentUser'] = Auth::user();

        return $this->render($layout, $data);
    }
}