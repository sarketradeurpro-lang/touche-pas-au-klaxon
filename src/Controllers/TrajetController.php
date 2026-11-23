<?php
/**
 * Contrôleur des trajets.
 *
 * Gère la consultation, la création, la modification et la suppression des
 * trajets avec les contrôles de cohérence métier demandés :
 *   - agence de départ != agence d'arrivée ;
 *   - date d'arrivée strictement postérieure à la date de départ ;
 *   - places disponibles comprises entre 0 et le total.
 * Toute opération d'écriture est suivie d'un message flash puis d'une
 * redirection vers la liste.
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
use TouchePasAuKlaxon\Models\Trajet;
use TouchePasAuKlaxon\Models\Employe;

/**
 * CRUD des trajets.
 */
final class TrajetController extends Controller
{
    /**
     * Affiche le détail d'un trajet (fenêtre modale côté client).
     *
     * @param Request $request Requête entrante.
     */
    public function show(Request $request): void
    {
        $id = (int) ($request->routeParam('id') ?? 0);
        $trajet = (new Trajet())->find($id);

        if ($trajet === null) {
            Flash::error('Trajet introuvable.');
            $this->redirect('/');
        }

        $agenceDepart = (new Agence())->find($trajet->getAgenceDepartId());
        $agenceArrivee = (new Agence())->find($trajet->getAgenceArriveeId());
        $auteur = (new Employe())->find($trajet->getAuteurId());

        $this->render('trajets/show', [
            'trajet'        => $trajet,
            'agenceDepart'  => $agenceDepart,
            'agenceArrivee' => $agenceArrivee,
            'auteur'        => $auteur,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un trajet (utilisateur connecté).
     *
     * @param Request $request Requête entrante.
     */
    public function create(Request $request): void
    {
        Auth::requireLogin();
        $this->render('trajets/form', [
            'agences' => (new Agence())->all(),
            'action'  => '/trajets/creer',
            'trajet'  => null,
        ]);
    }

    /**
     * Enregistre un nouveau trajet (contrôles de cohérence).
     *
     * @param Request $request Requête entrante.
     */
    public function store(Request $request): void
    {
        Auth::requireLogin();

        $data = $this->validate($request);
        $errors = $data['errors'];
        if (!empty($errors)) {
            Flash::error(implode(' ', $errors));
            $this->render('trajets/form', [
                'agences' => (new Agence())->all(),
                'action'  => '/trajets/creer',
                'trajet'  => null,
                'old'     => $data['data'],
            ]);
            return;
        }

        $user = Auth::user();
        $data['data']['auteur_id'] = $user->getId();

        (new Trajet())->create($data['data']);
        Flash::success('Le trajet a bien été créé.');
        $this->redirect('/');
    }

    /**
     * Affiche le formulaire de modification d'un trajet (auteur uniquement).
     *
     * @param Request $request Requête entrante.
     */
    public function edit(Request $request): void
    {
        Auth::requireLogin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $trajet = (new Trajet())->find($id);

        if ($trajet === null || !$this->canManage($trajet)) {
            Flash::error('Vous ne pouvez pas modifier ce trajet.');
            $this->redirect('/');
        }

        $this->render('trajets/form', [
            'agences' => (new Agence())->all(),
            'action'  => '/trajets/' . $id . '/modifier',
            'trajet'  => $trajet,
        ]);
    }

    /**
     * Applique la modification d'un trajet.
     *
     * @param Request $request Requête entrante.
     */
    public function update(Request $request): void
    {
        Auth::requireLogin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $trajet = (new Trajet())->find($id);

        if ($trajet === null) {
            Flash::error('Trajet introuvable.');
            $this->redirect('/');
        }
        if (!$this->canManage($trajet)) {
            Flash::error('Vous ne pouvez pas modifier ce trajet.');
            $this->redirect('/');
        }

        $data = $this->validate($request);
        $errors = $data['errors'];
        if (!empty($errors)) {
            Flash::error(implode(' ', $errors));
            $this->render('trajets/form', [
                'agences' => (new Agence())->all(),
                'action'  => '/trajets/' . $id . '/modifier',
                'trajet'  => $trajet,
                'old'     => $data['data'],
            ]);
            return;
        }

        (new Trajet())->update($id, $data['data']);
        Flash::success('Le trajet a bien été mis à jour.');
        $this->redirect('/');
    }

    /**
     * Supprime un trajet (son auteur, ou un administrateur).
     *
     * @param Request $request Requête entrante.
     */
    public function destroy(Request $request): void
    {
        Auth::requireLogin();
        $id = (int) ($request->routeParam('id') ?? 0);
        $trajet = (new Trajet())->find($id);

        if ($trajet === null) {
            Flash::error('Trajet introuvable.');
            $this->redirect('/');
        }
        if (!$this->canManage($trajet)) {
            Flash::error('Vous ne pouvez pas supprimer ce trajet.');
            $this->redirect('/');
        }

        (new Trajet())->delete($id);
        Flash::success('Le trajet a bien été supprimé.');
        $this->redirect('/');
    }

    /**
     * Vérifie que l'utilisateur courant peut gérer (modifier/supprimer) un
     * trajet : c'est son auteur, ou un administrateur.
     *
     * @param Trajet $trajet Trajet concerné.
     *
     * @return bool Vrai si autorisé.
     */
    private function canManage(Trajet $trajet): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->getId() === $trajet->getAuteurId();
    }

    /**
     * Récupère et valide les données du formulaire.
     *
     * @param Request $request Requête entrante.
     *
     * @return array{data: array<string, mixed>, errors: array<int, string>}
     *               Données normalisées et liste d'erreurs (vide si valide).
     */
    private function validate(Request $request): array
    {
        $agenceDepartId    = (int) ($request->post('agence_depart_id') ?? 0);
        $agenceArriveeId   = (int) ($request->post('agence_arrivee_id') ?? 0);
        $dateDepart        = trim((string) ($request->post('date_depart') ?? ''));
        $dateArrivee       = trim((string) ($request->post('date_arrivee') ?? ''));
        $nbPlacesTotal     = (int) ($request->post('nb_places_total') ?? 0);
        $nbPlacesDispo     = (int) ($request->post('nb_places_disponibles') ?? 0);

        $errors = [];

        // Agences : nécessaires et distinctes.
        if ($agenceDepartId <= 0 || $agenceArriveeId <= 0) {
            $errors[] = 'Les agences de départ et d\'arrivée sont obligatoires.';
        }
        if ($agenceDepartId === $agenceArriveeId) {
            $errors[] = 'L\'agence de départ et l\'agence d\'arrivée doivent être différentes.';
        }

        // Dates : départ puis arrivée postérieure.
        if ($dateDepart === '' || $dateArrivee === '') {
            $errors[] = 'Les dates de départ et d\'arrivée sont obligatoires.';
        } else {
            // Conversion en objets pour comparer proprement.
            $depart = \DateTime::createFromFormat('Y-m-d\TH:i', $dateDepart);
            $arrivee = \DateTime::createFromFormat('Y-m-d\TH:i', $dateArrivee);
            if ($depart === false || $arrivee === false) {
                $errors[] = 'Le format des dates est invalide.';
            } elseif ($arrivee <= $depart) {
                $errors[] = 'La date d\'arrivée doit être postérieure à la date de départ.';
            }
        }

        // Places.
        if ($nbPlacesTotal <= 0) {
            $errors[] = 'Le nombre total de places doit être supérieur à zéro.';
        }
        if ($nbPlacesDispo < 0 || $nbPlacesDispo > $nbPlacesTotal) {
            $errors[] = 'Le nombre de places disponibles doit être compris entre 0 et le total.';
        }

        // Normalisation SQL : passage de "Y-m-dTH:i" à "Y-m-d H:i:s".
        $dateDepartSql = $dateDepart !== '' ? str_replace('T', ' ', $dateDepart) . ':00' : '';
        $dateArriveeSql = $dateArrivee !== '' ? str_replace('T', ' ', $dateArrivee) . ':00' : '';

        return [
            'data' => [
                'agence_depart_id'      => $agenceDepartId,
                'agence_arrivee_id'     => $agenceArriveeId,
                'date_depart'           => $dateDepartSql,
                'date_arrivee'          => $dateArriveeSql,
                'nb_places_total'       => $nbPlacesTotal,
                'nb_places_disponibles' => $nbPlacesDispo,
            ],
            'errors' => $errors,
        ];
    }
}