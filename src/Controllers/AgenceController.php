<?php
/**
 * Contrôleur des agences (espace administrateur).
 *
 * Seul un administrateur peut lister, créer, modifier et supprimer les
 * agences. Comme pour les trajets, chaque écriture est suivie d'un message
 * flash puis d'une redirection vers la liste.
 *
 * @package TouchePasAuKlaxon\Controllers
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Controllers;

use TouchePasAuKlaxon\Core\Controller;
use TouchePasAuKlaxon\Core\Request;
use TouchePasAuKlaxon\Core\Auth;
use TouchePasAuKlaxon\Core\Flash;
use TouchePasAuKlaxon\Models\Agence;

/**
 * CRUD des agences (réservé à l'administrateur).
 */
final class AgenceController extends Controller
{
    /**
     * Liste les agences.
     *
     * @param Request $request Requête entrante.
     */
    public function index(Request $request): void
    {
        Auth::requireAdmin();
        $this->render('admin/agences/index', ['agences' => (new Agence())->all()]);
    }

    /**
     * Formulaire de création d'une agence.
     *
     * @param Request $request Requête entrante.
     */
    public function create(Request $request): void
    {
        Auth::requireAdmin();
        $this->render('admin/agences/form', ['ville' => '']);
    }

    /**
     * Enregistre une nouvelle agence.
     *
     * @param Request $request Requête entrante.
     */
    public function store(Request $request): void
    {
        Auth::requireAdmin();
        $ville = trim((string) ($request->post('ville') ?? ''));

        if ($ville === '') {
            Flash::error('Le nom de la ville est obligatoire.');
            $this->render('admin/agences/form', ['ville' => $ville]);
            return;
        }

        (new Agence())->create($ville);
        Flash::success('L\'agence « ' . $ville . ' » a bien été créée.');
        $this->redirect('/admin/agences');
    }

    /**
     * Formulaire de modification d'une agence.
     *
     * @param Request $request Requête entrante.
     */
    public function edit(Request $request): void
    {
        Auth::requireAdmin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $agence = (new Agence())->find($id);

        if ($agence === null) {
            Flash::error('Agence introuvable.');
            $this->redirect('/admin/agences');
        }

        $this->render('admin/agences/form', ['ville' => $agence->getVille(), 'id' => $id]);
    }

    /**
     * Applique la modification d'une agence.
     *
     * @param Request $request Requête entrante.
     */
    public function update(Request $request): void
    {
        Auth::requireAdmin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $ville = trim((string) ($request->post('ville') ?? ''));

        if ($ville === '') {
            Flash::error('Le nom de la ville est obligatoire.');
            $this->render('admin/agences/form', ['ville' => $ville, 'id' => $id]);
            return;
        }

        (new Agence())->update($id, $ville);
        Flash::success('L\'agence a bien été mise à jour.');
        $this->redirect('/admin/agences');
    }

    /**
     * Supprime une agence.
     *
     * @param Request $request Requête entrante.
     */
    public function destroy(Request $request): void
    {
        Auth::requireAdmin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $agence = (new Agence())->find($id);

        if ($agence === null) {
            Flash::error('Agence introuvable.');
            $this->redirect('/admin/agences');
        }

        (new Agence())->delete($id);
        Flash::success('L\'agence « ' . $agence->getVille() . ' » a bien été supprimée.');
        $this->redirect('/admin/agences');
    }
}