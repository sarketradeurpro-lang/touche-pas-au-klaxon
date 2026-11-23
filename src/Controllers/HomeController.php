<?php
/**
 * Contrôleur de la page d'accueil.
 *
 * Affiche la liste des trajets futurs disposant de places libres, triés par
 * date de départ croissante. Accessible à tous (visiteur ou connecté).
 *
 * @package TouchePasAuKlaxon\Controllers
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Controllers;

use TouchePasAuKlaxon\Core\Controller;
use TouchePasAuKlaxon\Core\Request;
use TouchePasAuKlaxon\Models\Trajet;

/**
 * Page d'accueil : liste des trajets disponibles.
 */
final class HomeController extends Controller
{
    /**
     * Affiche la liste des trajets disponibles.
     *
     * @param Request $request Requête entrante.
     */
    public function index(Request $request): void
    {
        $trajets = (new Trajet())->upcomingWithDetails();

        // Formate les dates pour l'affichage.
        foreach ($trajets as &$trajet) {
            $trajet['date_depart_fr']  = date('d/m/Y H\hi', strtotime($trajet['date_depart']));
            $trajet['date_arrivee_fr'] = date('d/m/Y H\hi', strtotime($trajet['date_arrivee']));
        }
        unset($trajet);

        $this->render('home/index', ['trajets' => $trajets]);
    }
}