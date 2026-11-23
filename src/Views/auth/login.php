<?php
/**
 * Page de connexion.
 *
 * @var string $email Email pré-rempli (en cas d'échec précédent).
 */

$email = $email ?? '';
?>
<div class="login-wrap tpk-card">
    <div class="card-header">Connexion</div>
    <div class="card-body">
        <form method="post" action="/login">
            <label>Adresse email</label>
            <input class="tpk-input" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required autofocus>

            <label>Mot de passe</label>
            <input class="tpk-input" type="password" name="password" required>

            <div style="margin-top:1.25rem;">
                <button class="tpk-btn" type="submit" style="width:100%;">Se connecter</button>
            </div>
            <p class="tpk-form-hint" style="text-align:center; margin-top:1rem;">
                Identifiants de démonstration fournis dans le dossier PDF.
            </p>
        </form>
    </div>
</div>