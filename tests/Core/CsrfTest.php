<?php
/**
 * Tests unitaires — jeton CSRF.
 *
 * @package TouchePasAuKlaxon\Tests\Core
 */

declare(strict_types=1);

namespace TouchePasAuKlaxon\Tests\Core;

use PHPUnit\Framework\TestCase;
use TouchePasAuKlaxon\Core\Csrf;

/**
 * Vérifie la génération, la stabilité et la validation du jeton CSRF.
 */
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableAndHexadecimal(): void
    {
        $token = Csrf::token();

        $this->assertSame($token, Csrf::token(), 'Le jeton doit rester stable pendant la session.');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testValidateAcceptsMatchingToken(): void
    {
        $token = Csrf::token();

        $this->assertTrue(Csrf::validate($token));
    }

    public function testValidateRejectsWrongOrMissingToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::validate('jeton-invalide'));
        $this->assertFalse(Csrf::validate(null));
        $this->assertFalse(Csrf::validate(123));
    }

    public function testFieldContainsHiddenInputAndToken(): void
    {
        $field = Csrf::field();

        $this->assertStringContainsString('name="_csrf"', $field);
        $this->assertStringContainsString(Csrf::token(), $field);
    }
}
