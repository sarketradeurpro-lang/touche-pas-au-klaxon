<?php
/**
 * Liste des employés (lecture seule, données RH).
 *
 * @var \TouchePasAuKlaxon\Models\Employe[] $employes
 */
?>
<h1>Employés</h1>
<p class="tpk-form-hint">Liste issue du système RH (fichier users.txt) — lecture seule, aucune modification possible depuis l'application.</p>

<div class="tpk-card">
    <div class="card-body">
        <table class="tpk-table">
            <thead>
                <tr>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employes as $emp): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst(mb_strtolower($emp->getPrenom()))) ?></td>
                        <td><?= htmlspecialchars(mb_strtoupper($emp->getNom())) ?></td>
                        <td><?= htmlspecialchars($emp->getEmail()) ?></td>
                        <td><?= htmlspecialchars((string) $emp->getTelephone()) ?></td>
                        <td>
                            <span class="tpk-badge tpk-badge-<?= $emp->isAdmin() ? 'warning' : 'primary' ?>">
                                <?= $emp->isAdmin() ? 'Administrateur' : 'Employé' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>