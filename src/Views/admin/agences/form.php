<?php
/**
 * Formulaire de création / modification d'une agence.
 *
 * @var string $ville Valeur actuelle de la ville.
 * @var int|null $id   Identifiant (en éditions) ou null.
 */

$isEdit = isset($id) && $id !== null;
$action = $isEdit ? '/admin/agences/' . (int) $id . '/modifier' : '/admin/agences/creer';
?>
<div class="tpk-card" style="max-width:520px;">
    <div class="card-header"><?= $isEdit ? 'Modifier l\'agence' : 'Ajouter une agence' ?></div>
    <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($action) ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>">
            <label>Ville</label>
            <input class="tpk-input" type="text" name="ville" value="<?= htmlspecialchars($ville) ?>" required>
            <div style="margin-top:1.25rem; display:flex; gap:.5rem;">
                <button class="tpk-btn tpk-btn-success" type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
                <a class="tpk-btn tpk-btn-secondary" href="/admin/agences">Retour</a>
            </div>
        </form>
    </div>
</div>