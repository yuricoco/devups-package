<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private function key(string $prefix): string
    {
        return $prefix . ':' . bin2hex(random_bytes(8));
    }

    public function testAllowsUpToMaxAttempts(): void
    {
        $limiter = new \RateLimiter($this->key('rl'), 60, 3);

        $this->assertFalse($limiter->tooManyAttempts());
        $limiter->hit();
        $this->assertFalse($limiter->tooManyAttempts());
        $limiter->hit();
        $this->assertFalse($limiter->tooManyAttempts());
        $limiter->hit();
        $this->assertTrue($limiter->tooManyAttempts(), 'le 3e echec doit declencher la limite (max=3)');
    }

    public function testClearResetsTheCounter(): void
    {
        $limiter = new \RateLimiter($this->key('rl'), 60, 2);
        $limiter->hit();
        $limiter->hit();
        $this->assertTrue($limiter->tooManyAttempts());

        $limiter->clear();

        $this->assertFalse($limiter->tooManyAttempts());
    }

    public function testWindowExpiryResetsTheCounter(): void
    {
        $limiter = new \RateLimiter($this->key('rl'), 1, 1);
        $limiter->hit();
        $this->assertTrue($limiter->tooManyAttempts());

        sleep(2);

        $this->assertFalse($limiter->tooManyAttempts(), 'la fenetre expiree doit remettre le compteur a zero');
    }

    public function testDistinctKeysDoNotInterfere(): void
    {
        $a = new \RateLimiter($this->key('rl_a'), 60, 1);
        $b = new \RateLimiter($this->key('rl_b'), 60, 1);

        $a->hit();

        $this->assertTrue($a->tooManyAttempts());
        $this->assertFalse($b->tooManyAttempts());
    }

    public function testConcurrentHitsAreNotLost(): void
    {
        $key = $this->key('rl_concurrent');
        $limiter = new \RateLimiter($key, 60, 1000);

        // Simule des requetes paralleles : chaque instance tient son propre
        // descripteur de fichier, le verrou (flock) doit tout de meme
        // serialiser les ecritures sans perdre de tentative.
        for ($i = 0; $i < 20; $i++) {
            (new \RateLimiter($key, 60, 1000))->hit();
        }

        $final = new \RateLimiter($key, 60, 20);
        $this->assertTrue($final->tooManyAttempts(), 'les 20 tentatives doivent toutes avoir ete comptees');
    }

    public function testRetryAfterIsBoundedByTheWindow(): void
    {
        $limiter = new \RateLimiter($this->key('rl'), 30, 1);
        $limiter->hit();

        $retryAfter = $limiter->retryAfter();

        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(30, $retryAfter);
    }
}
