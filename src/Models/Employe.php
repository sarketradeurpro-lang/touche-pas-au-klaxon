<?php
/**
 * Modèle Employe.
 *
 * Représente un employé de l'entreprise. Les employés proviennent du système
 * RH (fichier « users.txt ») et ne sont **pas** modifiables depuis
 * l'application (aucun CRUD employé).
 *
 * @package TouchePasAuKlaxon\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Models;

use TouchePasAuKlaxon\Core\Database;
use PDO;

/**
 * Entité employé.
 */
final class Employe
{
    private ?int $id = null;
    private string $nom;
    private string $prenom;
    private string $email;
    private ?string $telephone;
    private string $role;
    private string $motDePasse;

    /**
     * Retrouve un employé par son identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return Employe|null Employé trouvé ou null.
     */
    public function find(int $id): ?self
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM employes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Retrouve un employé par son adresse email.
     *
     * @param string $email Adresse email.
     *
     * @return Employe|null Employé trouvé ou null.
     */
    public function findByEmail(string $email): ?self
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM employes WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Liste tous les employés triés par nom puis prénom.
     *
     * @return array<int, Employe> Liste des employés.
     */
    public function all(): array
    {
        $rows = Database::pdo()
            ->query('SELECT * FROM employes ORDER BY nom, prenom')
            ->fetchAll();

        return array_map(static fn (array $row): self => self::fromRow($row), $rows);
    }

    /**
     * Hydrate une entité depuis une ligne de résultat SQL.
     *
     * @param array<string, mixed> $row Ligne de base.
     *
     * @return Employe Entité construite.
     */
    private static function fromRow(array $row): self
    {
        $e = new self();
        $e->id = (int) $row['id'];
        $e->nom = (string) $row['nom'];
        $e->prenom = (string) $row['prenom'];
        $e->email = (string) $row['email'];
        $e->telephone = isset($row['telephone']) && $row['telephone'] !== null ? (string) $row['telephone'] : null;
        $e->role = (string) $row['role'];
        $e->motDePasse = (string) $row['mot_de_passe'];

        return $e;
    }

    /**
     * Vérifie le mot de passe fourni contre le hash stocké.
     *
     * @param string $plain Mot de passe en clair.
     *
     * @return bool Vrai si le mot de passe est correct.
     */
    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->motDePasse);
    }

    /**
     * Identifiant de l'employé.
     *
     * @return int|null Identifiant.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Nom de l'employé.
     *
     * @return string Nom.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Prénom de l'employé.
     *
     * @return string Prénom.
     */
    public function getPrenom(): string
    {
        return $this->prenom;
    }

    /**
     * Nom complet « Prénom NOM ».
     *
     * @return string Nom complet.
     */
    public function getNomComplet(): string
    {
        return ucfirst(mb_strtolower($this->prenom)) . ' ' . mb_strtoupper($this->nom);
    }

    /**
     * Adresse email.
     *
     * @return string Email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Numéro de téléphone.
     *
     * @return string|null Téléphone.
     */
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    /**
     * Rôle (ROLE_USER / ROLE_ADMIN).
     *
     * @return string Rôle.
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Indique si l'employé est administrateur.
     *
     * @return bool Vrai si administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'ROLE_ADMIN';
    }
}