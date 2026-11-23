<?php
/**
 * Micro-framework de test — reproduit un sous-ensemble de l'API PHPUnit.
 *
 * Ce fichier ne sert que lorsque Composer n'est pas disponible (mode hors
 * ligne) : le runner maison (`bin/phpunit`) déclare alors une classe
 * `PHPUnit\Framework\TestCase` compatible. Si `composer install` a été
 * exécuté, le vrai PHPUnit est chargé en priorité (vendor/autoload.php)
 * et ce stub n'est pas utilisé.
 *
 * @package TouchePasAuKlaxon\Tests\Stubs
 */

declare(strict_types=1);

namespace PHPUnit\Framework;

/**
 * Implémentation minimale compatible PHPUnit 9/10 pour les tests sans Composer.
 */
abstract class TestCase
{
    /** Assertions échouées. */
    private array $failures = [];

    /** Nombre d'assertions passées. */
    private int $assertionCount = 0;

    /**
     * Méthodes de cycle de vie (à surcharger dans les tests).
     */
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    /**
     * Point d'entrée public du runner maison : exécute setUp().
     */
    public function runSetUp(): void
    {
        $this->setUp();
    }

    /**
     * Point d'entrée public du runner maison : exécute tearDown().
     */
    public function runTearDown(): void
    {
        $this->tearDown();
    }

    /**
     * Marque une assertion comme réussie.
     */
    private function pass(): void
    {
        $this->assertionCount++;
    }

    /**
     * Enregistre un échec d'assertion.
     *
     * @param string $message Message explicite.
     */
    private function fail(string $message): void
    {
        $this->failures[] = $message;
    }

    /**
     * Asserte le succès (booléen vrai).
     *
     * @param bool   $condition Condition à vérifier.
     * @param string $message   Message en cas d'échec.
     */
    public function assertTrue($condition, string $message = ''): void
    {
        $this->assertSame(true, $condition, $message);
    }

    /**
     * Asserte l'échec (booléen faux).
     *
     * @param bool   $condition Condition à vérifier.
     * @param string $message   Message en cas d'échec.
     */
    public function assertFalse($condition, string $message = ''): void
    {
        $this->assertSame(false, $condition, $message);
    }

    /**
     * Asserte l'égalité stricte.
     *
     * @param mixed  $expected Valeur attendue.
     * @param mixed  $actual   Valeur réelle.
     * @param string $message  Message en cas d'échec.
     */
    public function assertSame($expected, $actual, string $message = ''): void
    {
        $this->pass();
        if ($expected !== $actual) {
            $this->fail(
                ($message !== '' ? $message . ' — ' : '')
                . 'attendu ' . $this->dump($expected)
                . ', obtenu ' . $this->dump($actual)
            );
        }
    }

    /**
     * Asserte la non-nullité.
     *
     * @param mixed  $actual  Valeur à vérifier.
     * @param string $message Message en cas d'échec.
     */
    public function assertNotNull($actual, string $message = ''): void
    {
        $this->pass();
        if ($actual === null) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'la valeur ne doit pas être null.');
        }
    }

    /**
     * Asserte la nullité.
     *
     * @param mixed  $actual  Valeur à vérifier.
     * @param string $message Message en cas d'échec.
     */
    public function assertNull($actual, string $message = ''): void
    {
        $this->pass();
        if ($actual !== null) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'la valeur doit être null, obtenu ' . $this->dump($actual));
        }
    }

    /**
     * Asserte l'appartenance à une liste.
     *
     * @param mixed         $needle   Valeur cherchée.
     * @param iterable<array-key, mixed> $haystack Collection.
     * @param string        $message  Message en cas d'échec.
     */
    public function assertContains($needle, iterable $haystack, string $message = ''): void
    {
        $this->pass();
        if (!\in_array($needle, is_array($haystack) ? $haystack : iterator_to_array($haystack), true)) {
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . 'la valeur ' . $this->dump($needle) . ' doit être présente.');
        }
    }

    /**
     * Asserte la non-appartenance à une liste.
     *
     * @param mixed                $needle   Valeur cherchée.
     * @param iterable<array-key, mixed> $haystack Collection.
     * @param string               $message  Message en cas d'échec.
     */
    public function assertNotContains($needle, iterable $haystack, string $message = ''): void
    {
        $this->pass();
        if (\in_array($needle, is_array($haystack) ? $haystack : iterator_to_array($haystack), true)) {
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . 'la valeur ' . $this->dump($needle) . ' ne doit pas être présente.');
        }
    }

    /**
     * Asserte un entier supérieur à zéro.
     *
     * @param int    $expected Minimum (borné inférieur).
     * @param int    $actual   Valeur réelle.
     * @param string $message  Message en cas d'échec.
     */
    public function assertGreaterThan(int $expected, $actual, string $message = ''): void
    {
        $this->pass();
        if ($actual <= $expected) {
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . $this->dump($actual) . ' doit être strictement supérieur à ' . $expected . '.');
        }
    }

    /**
     * Résultat du test : liste des échecs (vide si OK).
     *
     * @return array<int, string> Échecs.
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Nombre d'assertions passées.
     *
     * @return int Compteur.
     */
    public function getAssertionCount(): int
    {
        return $this->assertionCount;
    }

    /**
     * Représentation lisible d'une valeur.
     *
     * @param mixed $value Valeur.
     *
     * @return string Représentation.
     */
    private function dump($value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        if (\is_string($value)) {
            return "'$value'";
        }
        return \is_scalar($value) ? (string) $value : \json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}