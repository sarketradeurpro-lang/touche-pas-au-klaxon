<?php
/**
 * Liste des agences (administration).
 *
 * @var \TouchePasAuKlaxon\Models\Agence[] $agences
 */
?>
<h1>Agences</h1>

<div class="tpk-card">
    <div class="card-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <span class="tpk-form-hint"><?= count($agences) ?> agence(s) référencée(s).</span>
            <a class="tpk-btn" href="/admin/agences/creer">Ajouter une agence</a>
        </div>
        <div class="tpk-table-wrap table-responsive">
        <table class="tpk-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ville</th>
                    <th style="width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agences as $agence): ?>
                    <tr>
                        <td><?= $agence->getId() ?></td>
                        <td><?= htmlspecialchars($agence->getVille()) ?></td>
                        <td>
                            <a class="tpk-btn tpk-btn-outline" href="/admin/agences/<?= $agence->getId() ?>/modifier">Modifier</a>
                            <form method="post" action="/admin/agences/<?= $agence->getId() ?>/supprimer"
                                  style="display:inline;"
                                  onsubmit="return confirm('Supprimer l\'agence <?= htmlspecialchars(addslashes($agence->getVille())) ?> ?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>">
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