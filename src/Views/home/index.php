<?php
/**
 * Page d'accueil : liste des trajets disponibles.
 *
 * @var array<int, array<string, mixed>> $trajets Trajets futurs avec places disponibles.
 */

$hasDetailsUser = ($currentUser ?? null) !== null;
?>
<div class="tpk-card tpk-home<?= $hasDetailsUser ? ' tpk-has-user' : '' ?>">
    <div class="card-header tpk-page-title">Trajets disponibles</div>
    <div class="card-body">
        <?php if (empty($trajets)): ?>
            <p>Aucun trajet disponible pour le moment. <?= $hasDetailsUser ? 'Soyez le premier à en proposer un&nbsp;!' : 'Connectez-vous pour proposer un trajet.' ?></p>
        <?php else: ?>
            <div class="tpk-table-wrap table-responsive">
            <table class="tpk-table">
                <thead>
                    <tr>
                        <th>Départ</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Places</th>
                        <th class="tpk-action-col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trajets as $trajet): ?>
                        <tr>
                            <?php
                            // Séparation affichage date / heure pour reproduire les colonnes des maquettes.
                            $departParts  = preg_split('/\s+/', trim((string) $trajet['date_depart_fr']));
                            $arriveeParts = preg_split('/\s+/', trim((string) $trajet['date_arrivee_fr']));
                            ?>
                            <td><?= htmlspecialchars($trajet['agence_depart']) ?></td>
                            <td><?= htmlspecialchars($departParts[0] ?? '') ?></td>
                            <td><?= htmlspecialchars($departParts[1] ?? '') ?></td>
                            <td><?= htmlspecialchars($trajet['agence_arrivee']) ?></td>
                            <td><?= htmlspecialchars($arriveeParts[0] ?? '') ?></td>
                            <td><?= htmlspecialchars($arriveeParts[1] ?? '') ?></td>
                            <td>
                                <span class="tpk-badge tpk-badge-<?= $trajet['nb_places_disponibles'] > 0 ? 'success' : 'warning' ?>">
                                    <?= (int) $trajet['nb_places_disponibles'] ?> / <?= (int) $trajet['nb_places_total'] ?>
                                </span>
                            </td>
                            <td class="tpk-action-col">
                                <?php if ($hasDetailsUser): ?>
                                    <button class="tpk-btn tpk-btn-outline"
                                            onclick="openDetail(<?= (int) $trajet['id'] ?>)">Détails</button>
                                <?php else: ?>
                                    <span class="tpk-form-hint">Connexion requise</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasDetailsUser): ?>
    <!-- Fenêtre modale : détail d'un trajet (réservé à l'utilisateur connecté). -->
    <div id="trajetModal" class="tpk-modal" style="display:none;">
        <div class="tpk-modal-dialog" role="dialog" aria-modal="true" aria-label="Détail du trajet">
            <button type="button" class="tpk-modal-close" aria-label="Fermer" onclick="closeDetail()">&times;</button>
            <div id="trajetModalContent"></div>
        </div>
    </div>
    <script>
        function openDetail(id) {
            // Fragment HTML seul (sans header/footer) pour la modale.
            fetch('/trajets/' + id + '?partial=1', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(r => r.text())
                .then(html => {
                    document.getElementById('trajetModalContent').innerHTML = html;
                    document.getElementById('trajetModal').style.display = 'flex';
                });
        }
        function closeDetail() {
            document.getElementById('trajetModal').style.display = 'none';
        }
        document.addEventListener('click', function (e) {
            if (e.target.id === 'trajetModal') { closeDetail(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeDetail(); }
        });
    </script>
<?php else: ?>
    <p class="tpk-form-hint">Connectez-vous pour consulter le détail d'un trajet (proposeur, téléphone, email).</p>
<?php endif; ?>