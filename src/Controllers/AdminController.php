<?php
/**
 * Contrôleur de l'espace administrateur.
 *
 * Fournit le tableau de bord (indicateurs), la liste des utilisateurs
 * (employés, lecture seule) et la liste des trajets.
 *
 * @package TouchePasAuKlaxon\Controllers
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Controllers;

use TouchePasAuKlaxon\Core\Controller;
use TouchePasAuKlaxon\Core\Request;
use TouchePasAuKlaxon\Core\Auth;
use TouchePasAuKlaxon\Core\Database;
use TouchePasAuKlaxon\Models\Employe;
use TouchePasAuKlaxon\Models\Trajet;
use TouchePasAuKlaxon\Models\Agence;
use TouchePasAuKlaxon\Core\Flash;
use TouchePasAuKlaxon\Core\Csrf;

/**
 * Espace réservé à l'administrateur.
 */
final class AdminController extends Controller
{
    /**
     * Tableau de bord de l'administrateur.
     *
     * @param Request $request Requête entrante.
     */
    public function dashboard(Request $request): void
    {
        Auth::requireAdmin();

        $counts = [
            'employes' => (int) $this->db()->query('SELECT COUNT(*) FROM employes')->fetchColumn(),
            'agences'  => (int) $this->db()->query('SELECT COUNT(*) FROM agences')->fetchColumn(),
            'trajets'  => (int) $this->db()->query('SELECT COUNT(*) FROM trajets')->fetchColumn(),
        ];

        $this->render('admin/dashboard', ['counts' => $counts]);
    }

    /**
     * Liste les employés (lecture seule, données RH).
     *
     * @param Request $request Requête entrante.
     */
    public function employees(Request $request): void
    {
        Auth::requireAdmin();
        $this->render('admin/employes', ['employes' => (new Employe())->all()]);
    }

    /**
     * Liste complète des trajets pour l'administration.
     *
     * @param Request $request Requête entrante.
     */
    public function trajets(Request $request): void
    {
        Auth::requireAdmin();

        $trajets = (new Trajet())->allWithDetails();

        $this->render('admin/trajets/index', [
            'trajets' => $trajets,
            'csrfToken' => Csrf::token(),
        ]);
    }

    /**
     * Supprime un trajet (réservé à l'administrateur).
     *
     * @param Request $request Requête entrante.
     */
    public function supprimerTrajet(Request $request): void
    {
        Auth::requireAdmin();

        // Vérification du jeton CSRF.
        if (!Csrf::validate($request->post('_csrf'))) {
            Flash::error('Jeton CSRF invalide ou manquant. La suppression a été annulée.');
            $this->redirect('/admin/trajets');
        }

        $id = (int) ($request->routeParam('id') ?? 0);
        if ($id <= 0) {
            Flash::error('Identifiant de trajet invalide.');
            $this->redirect('/admin/trajets');
        }

        $trajet = (new Trajet())->find($id);
        if ($trajet === null) {
            Flash::error('Trajet introuvable.');
            $this->redirect('/admin/trajets');
        }

        (new Trajet())->delete($id);
        Flash::success('Le trajet a bien été supprimé.');
        $this->redirect('/admin/trajets');
    }
}