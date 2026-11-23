<?php
/**
 * Modèle Agence.
 *
 * Représente une agence de l'entreprise. La liste des agences n'est
 * modifiable que par un administrateur (CRUD agence).
 *
 * @package TouchePasAuKlaxon\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Models;

use TouchePasAuKlaxon\Core\Database;
use PDO;

/**
 * Entité agence.
 */
final class Agence
{
    private ?int $id = null;
    private string $ville;

    /**
     * Retrouve une agence par son identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return Agence|null Agence trouvée ou null.
     */
    public function find(int $id): ?self
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM agences WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Liste toutes les agences, triées par ville.
     *
     * @return array<int, Agence> Liste des agences.
     */
    public function all(): array
    {
        $rows = Database::pdo()
            ->query('SELECT * FROM agences ORDER BY ville')
            ->fetchAll();

        return array_map(static fn (array $row): self => self::fromRow($row), $rows);
    }

    /**
     * Crée une agence en base.
     *
     * @param string $ville Ville de l'agence.
     *
     * @return int Identifiant de la nouvelle agence.
     */
    public function create(string $ville): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO agences (ville) VALUES (:ville)');
        $stmt->execute([':ville' => $ville]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Met à jour le nom de ville d'une agence.
     *
     * @param int    $id    Identifiant de l'agence.
     * @param string $ville Nouvelle ville.
     */
    public function update(int $id, string $ville): void
    {
        $stmt = Database::pdo()->prepare('UPDATE agences SET ville = :ville WHERE id = :id');
        $stmt->execute([':ville' => $ville, ':id' => $id]);
    }

    /**
     * Supprime une agence.
     *
     * @param int $id Identifiant de l'agence.
     */
    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM agences WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Hydrate une entité depuis une ligne SQL.
     *
     * @param array<string, mixed> $row Ligne de base.
     *
     * @return Agence Entité construite.
     */
    private static function fromRow(array $row): self
    {
        $a = new self();
        $a->id = (int) $row['id'];
        $a->ville = (string) $row['ville'];

        return $a;
    }

    /**
     * Identifiant de l'agence.
     *
     * @return int|null Identifiant.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Ville de l'agence.
     *
     * @return string Ville.
     */
    public function getVille(): string
    {
        return $this->ville;
    }
}