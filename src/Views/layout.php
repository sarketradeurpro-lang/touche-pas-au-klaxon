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
/** @var string $content */
/** @var array{type: string, message: string}[] $flashMessages */
/** @var \TouchePasAuKlaxon\Models\Employe|null $currentUser */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <!-- Bootstrap 5.3.8 (fichier local, aucun CDN requis) -->
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $isAdmin = $currentUser !== null && $currentUser->getId() !== null && $currentUser->isAdmin(); ?>
<nav class="tpk-navbar">
    <a class="brand" href="<?= $isAdmin ? '/admin' : '/' ?>">Touche pas <span>au klaxon</span></a>
    <div class="tpk-nav">
        <ul>
            <?php if ($isAdmin): ?>
                <li><a href="/admin">Tableau de bord</a></li>
                <li><a href="/admin/agences">Agences</a></li>
                <li><a href="/admin/employes">Employés</a></li>
                <li><a href="/admin/trajets">Trajets</a></li>
                <li><a href="/">Site public</a></li>
            <?php else: ?>
                <li><a href="/">Accueil</a></li>
                <?php if ($currentUser !== null && $currentUser->getId() !== null): ?>
                    <li><a href="/trajets/creer">Proposer un trajet</a></li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($currentUser !== null && $currentUser->getId() !== null): ?>
                <li><a href="/logout">Se déconnecter</a></li>
                <li class="user-chip"><?= htmlspecialchars($currentUser->getNomComplet()) ?></li>
            <?php else: ?>
                <li><a href="/login">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main class="tpk-container">
    <?php foreach ($flashMessages as $flash): ?>
        <div class="tpk-alert tpk-alert-<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="tpk-footer">
    Touche pas au klaxon — © <?= date('Y') ?>
</footer>
<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>