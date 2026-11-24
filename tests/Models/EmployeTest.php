<?php
/**
 * Tests unitaires — modèle Employe (base SQLite en mémoire).
 *
 * @package TouchePasAuKlaxon\Tests\Models
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Tests\Models;

use PHPUnit\Framework\TestCase;
use TouchePasAuKlaxon\Core\Database;
use TouchePasAuKlaxon\Models\Employe;
use TouchePasAuKlaxon\Tests\Support\TestDatabase;

/**
 * Comportement de l'entité Employe.
 */
final class EmployeTest extends TestCase
{
    protected function setUp(): void
    {
        // Base isolée : aucun accès à la base MariaDB réelle.
        Database::setPdo(TestDatabase::create());
    }

    public function testFindByEmailReturnsExistingEmployee(): void
    {
        $employe = (new Employe())->findByEmail(TestDatabase::ADMIN_EMAIL);
        $this->assertNotNull($employe, 'L\'administrateur de test doit exister en base.');
        $this->assertTrue($employe->isAdmin());
    }

    public function testFindByEmailReturnsNullForUnknown(): void
    {
        $this->assertNull((new Employe())->findByEmail('inconnu@example.test'));
    }

    public function testHashedPasswordVerification(): void
    {
        $employe = (new Employe())->findByEmail(TestDatabase::ADMIN_EMAIL);
        $this->assertNotNull($employe);
        $this->assertTrue($employe->verifyPassword(TestDatabase::ADMIN_PASSWORD));
        $this->assertFalse($employe->verifyPassword('mauvais'));
    }
}
