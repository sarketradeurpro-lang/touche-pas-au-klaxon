<?php
/**
 * Tests unitaires — modèle Employe.
 *
 * Vérifie la recherche par email et la validation du mot de passe.
 *
 * @package TouchePasAuKlaxon\Tests\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Tests\Models;

use PHPUnit\Framework\TestCase;
use TouchePasAuKlaxon\Core\Database;
use TouchePasAuKlaxon\Models\Employe;

/**
 * Comportement de l'entité Employe.
 */
final class EmployeTest extends TestCase
{
    protected function setUp(): void
    {
        Database::setPdo(new \PDO(
            'mysql:host=127.0.0.1;port=3310;dbname=covoiturage;charset=utf8mb4',
            'root',
            'root',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        ));
    }

    public function testFindByEmailReturnsExistingEmployee(): void
    {
        $employe = (new Employe())->findByEmail('alexandre.martin@email.fr');
        $this->assertNotNull($employe, 'L\'administrateur doit exister en base.');
        $this->assertTrue($employe->isAdmin());
    }

    public function testFindByEmailReturnsNullForUnknown(): void
    {
        $this->assertNull((new Employe())->findByEmail('inconnu@example.com'));
    }

    public function testHashedPasswordVerification(): void
    {
        $employe = (new Employe())->findByEmail('alexandre.martin@email.fr');
        $this->assertNotNull($employe);
        // Le mot de passe par défaut de l'admin est « Admin_2024! ».
        $this->assertTrue($employe->verifyPassword('Admin_2024!'));
        $this->assertFalse($employe->verifyPassword('mauvais'));
    }
}