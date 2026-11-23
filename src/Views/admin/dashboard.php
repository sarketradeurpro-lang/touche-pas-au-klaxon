<?php
/**
 * Tableau de bord de l'administrateur.
 *
 * @var array $counts Indicateurs (employes, agences, trajets).
 */
?>
<h1>Tableau de bord</h1>
<div class="tpk-grid">
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) ($counts['employes'] ?? 0) ?></div><div class="label">Employés</div></div></div>
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) ($counts['agences'] ?? 0) ?></div><div class="label">Agences</div></div></div>
    <div class="tpk-card"><div class="tpk-stat"><div class="value"><?= (int) ($counts['trajets'] ?? 0) ?></div><div class="label">Trajets</div></div></div>
</div>

<div style="margin-top:1.5rem; display:flex; gap:.5rem; flex-wrap:wrap;">
    <a class="tpk-btn" href="/admin/agences">Gérer les agences</a>
    <a class="tpk-btn tpk-btn-secondary" href="/admin/employes">Liste des employés</a>
    <a class="tpk-btn tpk-btn-secondary" href="/">Voir les trajets</a>
</div>