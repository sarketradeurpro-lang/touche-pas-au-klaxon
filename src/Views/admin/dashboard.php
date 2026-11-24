<?php
/**
 * Tableau de bord de l'administrateur.
 *
 * @var array{employes: int, agences: int, trajets: int} $counts Indicateurs.
 */
?>
<h1>Tableau de bord</h1>
<div class="tpk-grid">
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) $counts['employes'] ?></div><div class="label">Employés</div></div></div>
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) $counts['agences'] ?></div><div class="label">Agences</div></div></div>
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) $counts['trajets'] ?></div><div class="label">Trajets</div></div></div>
</div>

<div style="margin-top:1.5rem; display:flex; gap:.5rem; flex-wrap:wrap;">
    <a class="tpk-btn" href="/admin/agences">Gérer les agences</a>
    <a class="tpk-btn tpk-btn-secondary" href="/admin/employes">Liste des employés</a>
    <a class="tpk-btn tpk-btn-secondary" href="/admin/trajets">Trajets</a>
    <a class="tpk-btn tpk-btn-secondary" href="/">Voir les trajets</a>
</div>