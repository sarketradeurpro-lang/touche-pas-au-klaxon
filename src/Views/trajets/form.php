<?php
/**
 * Formulaire de création / modification d'un trajet.
 *
 * @var \TouchePasAuKlaxon\Models\Agence[] $agences   Liste des agences.
 * @var string                              $action   URL de soumission.
 * @var \TouchePasAuKlaxon\Models\Trajet|null $trajet  Trajet en cours d'édition (ou null).
 * @var array<string, mixed>|null           $old     Valeurs précédentes (après erreur).
 * @var \TouchePasAuKlaxon\Models\Employe|null $currentUser Employé connecté.
 */

$isEdit = $trajet !== null;
$old = $old ?? [];

// Valeurs affichées (éditées d'abord, sinon re-tamponnées, sinon vierges).
$vinfo = function (string $k, string $fallback = '') use ($old, $trajet): string {
    if (isset($old[$k]) && $old[$k] !== '') return (string) $old[$k];
    if (isset($old[$k]) && $old[$k] === '' ) return '';
    if ($trajet !== null) {
        return match ($k) {
            'agence_depart_id' => (string) $trajet->getAgenceDepartId(),
            'agence_arrivee_id' => (string) $trajet->getAgenceArriveeId(),
            'date_depart' => date('Y-m-d\TH:i', strtotime($trajet->getDateDepart())),
            'date_arrivee' => date('Y-m-d\TH:i', strtotime($trajet->getDateArrivee())),
            'nb_places_total' => (string) $trajet->getNbPlacesTotal(),
            'nb_places_disponibles' => (string) $trajet->getNbPlacesDisponibles(),
            default => $fallback,
        };
    }
    return $fallback;
};
?>
<div class="tpk-card">
    <div class="card-header"><?= $isEdit ? 'Modifier le trajet' : 'Proposer un trajet' ?></div>
    <div class="card-body">
        <?php if ($currentUser !== null): ?>
            <?php /* Informations de l'utilisateur : pré-renseignées et non
                     modifiables (cahier des charges §3.6). */ ?>
            <div class="tpk-userbox">
                <div class="tpk-userbox-title">Vos informations (non modifiables)</div>
                <dl>
                    <dt>Nom et prénom</dt>
                    <dd><?= htmlspecialchars($currentUser->getPrenom() . ' ' . $currentUser->getNom()) ?></dd>

                    <dt>Adresse email</dt>
                    <dd><?= htmlspecialchars($currentUser->getEmail()) ?></dd>

                    <dt>Téléphone</dt>
                    <dd><?= htmlspecialchars((string) ($currentUser->getTelephone() ?? '—')) ?></dd>
                </dl>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($action) ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>">
            <div class="tpk-grid">
                <div>
                    <label>Agence de départ</label>
                    <select class="tpk-select" name="agence_depart_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($agences as $agence): ?>
                            <option value="<?= $agence->getId() ?>" <?= $vinfo('agence_depart_id') == (string) $agence->getId() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agence->getVille()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Agence d'arrivée</label>
                    <select class="tpk-select" name="agence_arrivee_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($agences as $agence): ?>
                            <option value="<?= $agence->getId() ?>" <?= $vinfo('agence_arrivee_id') == (string) $agence->getId() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agence->getVille()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tpk-form-hint">Les agences de départ et d'arrivée doivent être différentes.</p>
                </div>
                <div>
                    <label>Date et heure de départ (GDH)</label>
                    <input class="tpk-input" type="datetime-local" name="date_depart"
                           value="<?= htmlspecialchars($vinfo('date_depart')) ?>" required>

                    <label>Date et heure d'arrivée (GDH)</label>
                    <input class="tpk-input" type="datetime-local" name="date_arrivee"
                           value="<?= htmlspecialchars($vinfo('date_arrivee')) ?>" required>
                    <p class="tpk-form-hint">L'arrivée doit être postérieure au départ.</p>
                </div>
                <div>
                    <label>Nombre de places total</label>
                    <input class="tpk-input" type="number" name="nb_places_total" min="1"
                           value="<?= htmlspecialchars($vinfo('nb_places_total')) ?>" required>

                    <label>Nombre de places disponibles</label>
                    <input class="tpk-input" type="number" name="nb_places_disponibles" min="0"
                           value="<?= htmlspecialchars($vinfo('nb_places_disponibles')) ?>" required>
                </div>
            </div>

            <div style="margin-top:1.5rem; display:flex; gap:.5rem; align-items:center;">
                <button class="tpk-btn tpk-btn-success" type="submit"><?= $isEdit ? 'Enregistrer les modifications' : 'Créer le trajet' ?></button>
                <a class="tpk-btn tpk-btn-secondary" href="/">Annuler</a>
            </div>
        </form>
    </div>
</div>