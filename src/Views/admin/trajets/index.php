<?php
/**
 * Liste complète des trajets (administration).
 *
 * @var array<int, array<string, mixed>> $trajets Trajets enrichis.
 * @var string $csrfToken Jeton de protection CSRF.
 */
?>
<h1>Trajets</h1>

<p class="tpk-form-hint"><?= count($trajets) ?> trajet(s) référencé(s).</p>

<div class="tpk-card">
    <div class="card-body">
        <div class="tpk-table-wrap table-responsive">
        <table class="tpk-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Départ</th>
                    <th>Arrivée</th>
                    <th>Date / heure départ</th>
                    <th>Date / heure arrivée</th>
                    <th>Places</th>
                    <th>Proposant</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trajets as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($t['id'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($t['agence_depart'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($t['agence_arrivee'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($t['date_depart'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($t['date_arrivee'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) (($t['nb_places_disponibles'] ?? 0) . ' / ' . ($t['nb_places_total'] ?? 0))) ?></td>
                        <td>
                            <?= htmlspecialchars((string) (($t['auteur_prenom'] ?? '') . ' ' . ($t['auteur_nom'] ?? ''))) ?>
                            <br>
                            <small><?= htmlspecialchars((string) ($t['auteur_email'] ?? '')) ?></small>
                        </td>
                        <td>
                            <form method="post" action="/admin/trajets/<?= htmlspecialchars((string) ($t['id'] ?? '')) ?>/supprimer"
                                  style="display:inline;"
                                  onsubmit="return confirm('Supprimer ce trajet ?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button class="tpk-btn tpk-btn-danger" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
