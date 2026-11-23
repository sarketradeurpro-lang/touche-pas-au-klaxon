<?php
/**
 * Contrôleur d'authentification.
 *
 * Gère l'affichage du formulaire de connexion, la validation des
 * identifiants et la déconnexion.
 *
 * @package TouchePasAuKlaxon\Controllers
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Controllers;

use TouchePasAuKlaxon\Core\Controller;
use TouchePasAuKlaxon\Core\Request;
use TouchePasAuKlaxon\Core\Auth;
use TouchePasAuKlaxon\Core\Flash;
use TouchePasAuKlaxon\Models\Employe;

/**
 * Authentification des employés.
 */
final class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     *
     * @param Request $request Requête entrante.
     */
    public function showLogin(Request $request): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('/');
        }
        $this->render('auth/login');
    }

    /**
     * Traite la soumission du formulaire de connexion.
     *
     * @param Request $request Requête entrante.
     */
    public function login(Request $request): void
    {
        $email = trim((string) ($request->post('email') ?? ''));
        $password = (string) ($request->post('password') ?? '');

        if ($email === '' || $password === '') {
            Flash::error('Veuillez renseigner votre email et votre mot de passe.');
            $this->render('auth/login', ['email' => $email]);
            return;
        }

        $employe = (new Employe())->findByEmail($email);

        if ($employe === null || !$employe->verifyPassword($password)) {
            Flash::error('Identifiants incorrects.');
            $this->render('auth/login', ['email' => $email]);
            return;
        }

        Auth::login($employe->getId());
        Flash::success('Bonjour ' . $employe->getNomComplet() . ', vous êtes connecté(e).');
        $this->redirect('/');
    }

    /**
     * Déconnecte l'utilisateur courant.
     *
     * @param Request $request Requête entrante.
     */
    public function logout(Request $request): void
    {
        Auth::logout();
        Flash::info('Vous avez été déconnecté(e).');
        $this->redirect('/');
    }
}