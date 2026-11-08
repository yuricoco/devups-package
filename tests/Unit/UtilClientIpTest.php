<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE P.3 : Util::get_IP_address() ne doit faire confiance a
 * X-Forwarded-For (et assimiles) que si REMOTE_ADDR figure dans
 * TRUSTED_PROXIES - sinon n'importe quel client peut forger cet en-tete
 * pour usurper une IP et contourner le RateLimiter. TRUSTED_PROXIES vaut
 * '203.0.113.9' pour cette suite (phpunit.xml.dist) - vide par defaut en
 * production (config/constante.example.php).
 */
final class UtilClientIpTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    public function testForgedHeaderIsIgnoredFromAnUntrustedRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7'; // n'est pas dans TRUSTED_PROXIES
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';

        $this->assertSame('198.51.100.7', \DClass\lib\Util::get_IP_address());
    }

    public function testHeaderIsHonoredFromATrustedProxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9'; // TRUSTED_PROXIES (phpunit.xml.dist)
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';

        $this->assertSame('6.6.6.6', \DClass\lib\Util::get_IP_address());
    }

    public function testTrustedProxyHeaderUsesTheFirstValidPublicIpInTheChain(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.5, 6.6.6.6, 198.51.100.1';

        $this->assertSame('6.6.6.6', \DClass\lib\Util::get_IP_address());
    }

    public function testFallsBackToRemoteAddrWhenTheTrustedProxySendsNoUsableHeader(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED'],
            $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'], $_SERVER['HTTP_FORWARDED_FOR'], $_SERVER['HTTP_FORWARDED']);

        $this->assertSame('203.0.113.9', \DClass\lib\Util::get_IP_address());
    }
}
