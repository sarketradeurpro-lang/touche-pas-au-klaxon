<?php
/**
 * Modèle Trajet.
 *
 * Représente une proposition de covoiturage entre deux agences.
 * Vérifications métier appliquées à la création / mise à jour :
 *   - agence de départ != agence d'arrivée ;
 *   - date d'arrivée postérieure à la date de départ ;
 *   - places disponibles cohérentes (0 <= dispo <= total).
 *
 * @package TouchePasAuKlaxon\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Models;

use TouchePasAuKlaxon\Core\Database;
use PDO;
use DateTimeImmutable;

/**
 * Entité trajet.
 */
final class Trajet
{
    private ?int $id = null;
    private int $auteurId;
    private int $agenceDepartId;
    private int $agenceArriveeId;
    private string $dateDepart;
    private string $dateArrivee;
    private int $nbPlacesTotal;
    private int $nbPlacesDisponibles;

    /**
     * Retrouve un trajet par son identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return Trajet|null Trajet trouvé ou null.
     */
    public function find(int $id): ?self
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM trajets WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Liste les trajets futurs avec places disponibles, triés par date de
     * départ croissante. Joint les agences et l'auteur pour l'affichage.
     *
     * @return array<int, array<string, mixed>> Lignes brutes enrichies.
     */
    public function upcomingWithDetails(): array
    {
        $sql = '
            SELECT t.*,
                   ad.ville AS agence_depart,
                   aa.ville AS agence_arrivee,
                   e.nom  AS auteur_nom,
                   e.prenom AS auteur_prenom,
                   e.telephone AS auteur_telephone,
                   e.email AS auteur_email
            FROM trajets t
            JOIN agences ad ON ad.id = t.agence_depart_id
            JOIN agences aa ON aa.id = t.agence_arrivee_id
            JOIN employes e ON e.id = t.auteur_id
            WHERE t.date_depart > :now
              AND t.nb_places_disponibles > 0
            ORDER BY t.date_depart ASC';

        // Paramètre lié (et non NOW()) pour rester compatible MySQL et SQLite,
        // ce qui permet d'isoler les tests sur une base en mémoire.
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([':now' => (new DateTimeImmutable())->format('Y-m-d H:i:s')]);

        return $stmt->fetchAll();
    }

    /**
     * Liste tous les trajets (tous statuts) pour l'administration.
     *
     * @return array<int, array<string, mixed>> Lignes brutes enrichies.
     */
    public function allWithDetails(): array
    {
        $sql = '
            SELECT t.*,
                   ad.ville AS agence_depart,
                   aa.ville AS agence_arrivee,
                   e.nom  AS auteur_nom,
                   e.prenom AS auteur_prenom,
                   e.email AS auteur_email
            FROM trajets t
            JOIN agences ad ON ad.id = t.agence_depart_id
            JOIN agences aa ON aa.id = t.agence_arrivee_id
            JOIN employes e ON e.id = t.auteur_id
            ORDER BY t.date_depart DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    /**
     * Opération d'écriture : insertion d'un nouveau trajet.
     *
     * @param array<string, mixed> $data Données validées.
     *
     * @return int Identifiant du trajet créé.
     */
    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare('
            INSERT INTO trajets
                (auteur_id, agence_depart_id, agence_arrivee_id, date_depart,
                 date_arrivee, nb_places_total, nb_places_disponibles)
            VALUES
                (:auteur_id, :agence_depart_id, :agence_arrivee_id, :date_depart,
                 :date_arrivee, :nb_places_total, :nb_places_disponibles)');

        $stmt->execute([
            ':auteur_id'             => (int) $data['auteur_id'],
            ':agence_depart_id'      => (int) $data['agence_depart_id'],
            ':agence_arrivee_id'     => (int) $data['agence_arrivee_id'],
            ':date_depart'           => (string) $data['date_depart'],
            ':date_arrivee'          => (string) $data['date_arrivee'],
            ':nb_places_total'       => (int) $data['nb_places_total'],
            ':nb_places_disponibles' => (int) $data['nb_places_disponibles'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Opération d'écriture : mise à jour d'un trajet.
     *
     * @param int                 $id   Identifiant du trajet.
     * @param array<string, mixed> $data Données validées.
     */
    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare('
            UPDATE trajets SET
                agence_depart_id      = :agence_depart_id,
                agence_arrivee_id     = :agence_arrivee_id,
                date_depart           = :date_depart,
                date_arrivee          = :date_arrivee,
                nb_places_total       = :nb_places_total,
                nb_places_disponibles = :nb_places_disponibles
            WHERE id = :id');

        $stmt->execute([
            ':id'                     => $id,
            ':agence_depart_id'       => (int) $data['agence_depart_id'],
            ':agence_arrivee_id'      => (int) $data['agence_arrivee_id'],
            ':date_depart'            => (string) $data['date_depart'],
            ':date_arrivee'           => (string) $data['date_arrivee'],
            ':nb_places_total'        => (int) $data['nb_places_total'],
            ':nb_places_disponibles'  => (int) $data['nb_places_disponibles'],
        ]);
    }

    /**
     * Opération d'écriture : suppression d'un trajet.
     *
     * @param int $id Identifiant du trajet.
     */
    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM trajets WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Hydrate une entité depuis une ligne SQL.
     *
     * @param array<string, mixed> $row Ligne de base.
     *
     * @return Trajet Entité construite.
     */
    private static function fromRow(array $row): self
    {
        $t = new self();
        $t->id = (int) $row['id'];
        $t->auteurId = (int) $row['auteur_id'];
        $t->agenceDepartId = (int) $row['agence_depart_id'];
        $t->agenceArriveeId = (int) $row['agence_arrivee_id'];
        $t->dateDepart = (string) $row['date_depart'];
        $t->dateArrivee = (string) $row['date_arrivee'];
        $t->nbPlacesTotal = (int) $row['nb_places_total'];
        $t->nbPlacesDisponibles = (int) $row['nb_places_disponibles'];

        return $t;
    }

    /**
     * Identifiant du trajet.
     *
     * @return int|null Identifiant.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Identifiant de l'auteur.
     *
     * @return int Identifiant de l'employé proposeur.
     */
    public function getAuteurId(): int
    {
        return $this->auteurId;
    }

    /**
     * Identifiant de l'agence de départ.
     *
     * @return int Identifiant.
     */
    public function getAgenceDepartId(): int
    {
        return $this->agenceDepartId;
    }

    /**
     * Identifiant de l'agence d'arrivée.
     *
     * @return int Identifiant.
     */
    public function getAgenceArriveeId(): int
    {
        return $this->agenceArriveeId;
    }

    /**
     * Date / heure de départ.
     *
     * @return string Date de départ (format SQL).
     */
    public function getDateDepart(): string
    {
        return $this->dateDepart;
    }

    /**
     * Date / heure d'arrivée.
     *
     * @return string Date d'arrivée (format SQL).
     */
    public function getDateArrivee(): string
    {
        return $this->dateArrivee;
    }

    /**
     * Nombre total de places.
     *
     * @return int Total de places.
     */
    public function getNbPlacesTotal(): int
    {
        return $this->nbPlacesTotal;
    }

    /**
     * Nombre de places disponibles.
     *
     * @return int Places disponibles.
     */
    public function getNbPlacesDisponibles(): int
    {
        return $this->nbPlacesDisponibles;
    }
}