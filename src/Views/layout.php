<?php
/**
 * Gabarit global (header + contenu + footer).
 *
 * Variables disponibles :
 *   - $content        : HTML du contenu (produit par la vue appelée).
 *   - $flashMessages  : tableau de messages flash (type, message).
 *   - $currentUser    : employé connecté ou null.
 *
 * @package TouchePasAuKlaxon\Views
 */

$title = $title ?? 'Touche pas au klaxon';
/** @var array{type: string, message: string}[] $flashMessages */
/** @var \TouchePasAuKlaxon\Models\Employe|null $currentUser */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<nav class="tpk-navbar">
    <a class="brand" href="/">Touche pas <span>au klaxon</span></a>
    <div class="tpk-nav">
        <ul>
            <li><a href="/">Accueil</a></li>
            <?php if ($currentUser !== null && $currentUser->getId() !== null): ?>
                <li><a href="/trajets/creer">Proposer un trajet</a></li>
                <?php if ($currentUser->isAdmin()): ?>
                    <li><a href="/admin">Administration</a></li>
                <?php endif; ?>
                <li><a href="/logout">Se déconnecter</a></li>
                <li class="user-chip"><?= htmlspecialchars($currentUser->getNomComplet()) ?></li>
            <?php else: ?>
                <li><a href="/login">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main class="tpk-container">
    <?php foreach (($flashMessages ?? []) as $flash): ?>
        <div class="tpk-alert tpk-alert-<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="tpk-footer">
    Touche pas au klaxon — covoiturage inter-agences © <?= date('Y') ?>
</footer>
</body>
</html>