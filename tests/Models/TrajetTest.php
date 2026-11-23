<?php
/**
 * Tests unitaires — modèle Trajet (opérations en base).
 *
 * Exécution avec un serveur MariaDB lancé :
 *     bin/phpunit            (runner maison, équivalent PHPUnit)
 *     vendor/bin/phpunit     (si composer install a été réalisé)
 *
 * @package TouchePasAuKlaxon\Tests\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Tests\Models;

use PHPUnit\Framework\TestCase;
use TouchePasAuKlaxon\Core\Database;
use TouchePasAuKlaxon\Models\Trajet;

/**
 * Vérifie les opérations d'écriture (insertion, mise à jour, suppression)
 * et le filtre des trajets disponibles/passés.
 */
final class TrajetTest extends TestCase
{
    /** Lignes insérées lors du test pour pouvoir les nettoyer. */
    private array $createdIds = [];

    protected function setUp(): void
    {
        // Connexion à une base de test dédiée (configuration ci-dessous).
        Database::setPdo(new \PDO(
            'mysql:host=127.0.0.1;port=3310;dbname=covoiturage;charset=utf8mb4',
            'root',
            'root',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        ));
    }

    protected function tearDown(): void
    {
        // Nettoyage : supprime les trajets créés par les tests.
        $db = Database::pdo();
        foreach ($this->createdIds as $id) {
            $db->prepare('DELETE FROM trajets WHERE id = :id')
                ->execute([':id' => $id]);
        }
    }

    public function testCreateTrajet(): void
    {
        $trajet = new Trajet();
        $id = $trajet->create([
            'auteur_id'             => 1,
            'agence_depart_id'      => 1,
            'agence_arrivee_id'     => 2,
            'date_depart'           => '2099-01-01 08:00:00',
            'date_arrivee'          => '2099-01-01 10:00:00',
            'nb_places_total'       => 4,
            'nb_places_disponibles' => 3,
        ]);
        $this->createdIds[] = $id;

        $this->assertGreaterThan(0, $id, 'L\'insertion doit retourner un identifiant positif.');
        $fresh = (new Trajet())->find($id);
        $this->assertNotNull($fresh, 'Le trajet doit être relisible après insertion.');
        $this->assertSame(1, $fresh->getAgenceDepartId());
        $this->assertSame(2, $fresh->getAgenceArriveeId());
        $this->assertSame(3, $fresh->getNbPlacesDisponibles());
    }

    public function testUpdateTrajet(): void
    {
        $trajet = new Trajet();
        $id = $trajet->create([
            'auteur_id'             => 1,
            'agence_depart_id'      => 1,
            'agence_arrivee_id'     => 2,
            'date_depart'           => '2099-02-01 08:00:00',
            'date_arrivee'          => '2099-02-01 10:00:00',
            'nb_places_total'       => 3,
            'nb_places_disponibles' => 2,
        ]);
        $this->createdIds[] = $id;

        // Mise à jour des places et de l'agence d'arrivée.
        $trajet->update($id, [
            'agence_depart_id'      => 1,
            'agence_arrivee_id'     => 3,
            'date_depart'           => '2099-02-01 09:00:00',
            'date_arrivee'          => '2099-02-01 12:00:00',
            'nb_places_total'       => 5,
            'nb_places_disponibles' => 5,
        ]);

        $fresh = $trajet->find($id);
        $this->assertSame(3, $fresh->getAgenceArriveeId());
        $this->assertSame(5, $fresh->getNbPlacesTotal());
        $this->assertSame(5, $fresh->getNbPlacesDisponibles());
    }

    public function testDeleteTrajet(): void
    {
        $trajet = new Trajet();
        $id = $trajet->create([
            'auteur_id'             => 1,
            'agence_depart_id'      => 1,
            'agence_arrivee_id'     => 2,
            'date_depart'           => '2099-03-01 08:00:00',
            'date_arrivee'          => '2099-03-01 10:00:00',
            'nb_places_total'       => 2,
            'nb_places_disponibles' => 2,
        ]);

        $trajet->delete($id);
        $this->assertNull($trajet->find($id), 'Le trajet supprimé ne doit plus exister.');
    }

    public function testUpcomingOnlyReturnsFutureTrajets(): void
    {
        $trajet = new Trajet();
        // Trajet passé : ne doit JAMAIS apparaître dans la liste « à venir ».
        $pastId = $trajet->create([
            'auteur_id'             => 1,
            'agence_depart_id'      => 1,
            'agence_arrivee_id'     => 2,
            'date_depart'           => '2020-01-01 08:00:00',
            'date_arrivee'          => '2020-01-01 10:00:00',
            'nb_places_total'       => 4,
            'nb_places_disponibles' => 4,
        ]);
        $this->createdIds[] = $pastId;

        $ids = array_column($trajet->upcomingWithDetails(), 'id');
        $this->assertNotContains($pastId, $ids, 'Un trajet passé ne doit pas être listé.');
    }
}