<?php
/**
 * Page d'accueil : liste des trajets disponibles.
 *
 * @var array $trajets Liste des trajets futurs avec places disponibles,
 *                     chacun contenant les champs nécessaires à l'affichage.
 */

$hasDetailsUser = ($currentUser ?? null) !== null;
?>
<div class="tpk-card">
    <div class="card-header">Trajets disponibles</div>
    <div class="card-body">
        <?php if (empty($trajets)): ?>
            <p>Aucun trajet disponible pour le moment. <?= $hasDetailsUser ? 'Soyez le premier à en proposer un&nbsp;!' : 'Connectez-vous pour proposer un trajet.' ?></p>
        <?php else: ?>
            <table class="tpk-table">
                <thead>
                    <tr>
                        <th>Agence de départ</th>
                        <th>Date de départ</th>
                        <th>Agence d'arrivée</th>
                        <th>Date d'arrivée</th>
                        <th>Places disponibles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trajets as $trajet): ?>
                        <tr>
                            <td><?= htmlspecialchars($trajet['agence_depart']) ?></td>
                            <td><?= htmlspecialchars($trajet['date_depart_fr']) ?></td>
                            <td><?= htmlspecialchars($trajet['agence_arrivee']) ?></td>
                            <td><?= htmlspecialchars($trajet['date_arrivee_fr']) ?></td>
                            <td>
                                <span class="tpk-badge tpk-badge-<?= $trajet['nb_places_disponibles'] > 0 ? 'success' : 'warning' ?>">
                                    <?= (int) $trajet['nb_places_disponibles'] ?> / <?= (int) $trajet['nb_places_total'] ?>
                                </span>
                            </td>
                            <td>
                                <button class="tpk-btn tpk-btn-outline"
                                        onclick="openDetail(<?= (int) $trajet['id'] ?>)">Détails</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasDetailsUser): ?>
    <!-- Fenêtre modale : détail d'un trajet (accessible à l'utilisateur connecté). -->
    <div id="trajetModal" style="display:none;"></div>
    <script>
        function openDetail(id) {
            // Charge le détail du trajet dans la modale via un fetch léger.
            fetch('/trajets/' + id)
                .then(r => r.text())
                .then(html => {
                    const el = document.getElementById('trajetModal');
                    el.innerHTML = html;
                    el.style.display = 'block';
                });
        }
        document.addEventListener('click', function (e) {
            const modal = document.getElementById('trajetModal');
            if (e.target.id === 'trajetModal') { modal.style.display = 'none'; }
        });
    </script>
<?php else: ?>
    <p class="tpk-form-hint">Connectez-vous pour consulter le détail d'un trajet (proposeur, téléphone, email).</p>
<?php endif; ?>