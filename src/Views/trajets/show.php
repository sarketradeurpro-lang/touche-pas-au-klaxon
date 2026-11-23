<?php
/**
 * Détail d'un trajet (contenu chargé dans la fenêtre modale).
 *
 * @var \TouchePasAuKlaxon\Models\Trajet        $trajet
 * @var \TouchePasAuKlaxon\Models\Agence|null   $agenceDepart
 * @var \TouchePasAuKlaxon\Models\Agence|null   $agenceArrivee
 * @var \TouchePasAuKlaxon\Models\Employe|null  $auteur
 */

$canManage = ($currentUser ?? null) !== null
    && ($currentUser->isAdmin()
        || ($auteur !== null && $currentUser->getId() === $auteur->getId()));
?>
<div style="position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1050;">
    <div style="background:#fff; border-radius:var(--tpk-radius); max-width:560px; width:92%; box-shadow:var(--tpk-shadow); max-height:90vh; overflow:auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span>Détail du trajet</span>
            <button class="tpk-btn tpk-btn-secondary" onclick="document.getElementById('trajetModal').style.display='none'">Fermer</button>
        </div>
        <div class="card-body">
            <dl class="tpk-details">
                <dt>Agence de départ</dt>
                <dd><?= $agenceDepart ? htmlspecialchars($agenceDepart->getVille()) : '—' ?></dd>

                <dt>Date de départ</dt>
                <dd><?= htmlspecialchars(date('d/m/Y H\hi', strtotime($trajet->getDateDepart()))) ?></dd>

                <dt>Agence d'arrivée</dt>
                <dd><?= $agenceArrivee ? htmlspecialchars($agenceArrivee->getVille()) : '—' ?></dd>

                <dt>Date d'arrivée</dt>
                <dd><?= htmlspecialchars(date('d/m/Y H\hi', strtotime($trajet->getDateArrivee()))) ?></dd>

                <dt>Places</dt>
                <dd><?= $trajet->getNbPlacesDisponibles() ?> disponibles sur <?= $trajet->getNbPlacesTotal() ?></dd>

                <?php if ($auteur !== null): ?>
                    <dt>Proposé par</dt>
                    <dd><?= htmlspecialchars($auteur->getNomComplet()) ?></dd>

                    <dt>Téléphone</dt>
                    <dd><?= htmlspecialchars((string) $auteur->getTelephone()) ?></dd>

                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($auteur->getEmail()) ?></dd>
                <?php endif; ?>
            </dl>

            <?php if ($canManage): ?>
                <div style="display:flex; gap:.5rem;">
                    <a class="tpk-btn" href="/trajets/<?= $trajet->getId() ?>/modifier">Modifier</a>
                    <form method="post" action="/trajets/<?= $trajet->getId() ?>/supprimer"
                          onsubmit="return confirm('Supprimer ce trajet ?');">
                        <button class="tpk-btn tpk-btn-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>