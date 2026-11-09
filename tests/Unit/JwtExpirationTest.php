<?php

namespace Devups\Tests\Unit;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

/**
 * ETAPE A.6 (v7.1) : JWT_EXPIRE passe de "false" (defaut historique,
 * jeton sans expiration) a "+72 hours" dans .env.example - mais la
 * configuration EFFECTIVE des tests (.env local, hors depot) peut encore
 * valoir "false". Ces tests ne dependent donc PAS de jwt_expire_time a
 * l'execution : ils construisent directement un jeton de la MEME forme
 * que Auth::getJWT() en emet quand jwt_expire_time est une duree (iat/
 * nbf/exp), et verifient que Auth::decodeJWTOrFail() - qui delegue a
 * Firebase\JWT\JWT::decode(), lequel controle TOUJOURS exp/nbf quand ces
 * claims sont presentes, independamment de notre propre verification
 * applicative dans Auth::execute() - rejette bien un jeton expire et
 * accepte un jeton encore valide. C'est exactement le comportement que
 * produira n'importe quel deploiement avec JWT_EXPIRE regle sur une
 * duree (le nouveau defaut).
 */
final class JwtExpirationTest extends TestCase
{
    private function tokenWithExpiry(int $iatOffsetSeconds, int $expOffsetSeconds, int $userId = 42): string
    {
        $now = time();
        return JWT::encode([
            'iat' => $now + $iatOffsetSeconds,
            'iss' => __server,
            'nbf' => $now + $iatOffsetSeconds,
            'exp' => $now + $expOffsetSeconds,
            'userId' => $userId,
        ], jwt_secret_Key, 'HS512');
    }

    public function testAnExpiredTokenIsRejected(): void
    {
        // emis il y a 4 jours, expire depuis 1 jour - exactement la forme
        // que Auth::getJWT() produirait avec JWT_EXPIRE="+72 hours" une
        // fois le delai depasse.
        $jwt = $this->tokenWithExpiry(-4 * 86400, -1 * 86400);

        $this->expectException(\Throwable::class);
        \Auth::decodeJWTOrFail($jwt);
    }

    public function testATokenNotYetValidIsRejected(): void
    {
        // nbf dans le futur : pas encore valide.
        $jwt = $this->tokenWithExpiry(+3600, +86400);

        $this->expectException(\Throwable::class);
        \Auth::decodeJWTOrFail($jwt);
    }

    public function testAStillValidTokenWithExpiryClaimsIsAccepted(): void
    {
        $jwt = $this->tokenWithExpiry(0, 72 * 3600);

        $decoded = \Auth::decodeJWTOrFail($jwt);

        $this->assertSame(42, $decoded->userId);
        $this->assertSame(__server, $decoded->iss);
    }

    public function testATokenWithoutAnyExpiryClaimNeverExpires(): void
    {
        // la forme exacte que Auth::getJWT() emet quand jwt_expire_time
        // vaut false : ni iat, ni nbf, ni exp.
        $jwt = JWT::encode([
            'iss' => __server,
            'userId' => 7,
        ], jwt_secret_Key, 'HS512');

        $decoded = \Auth::decodeJWTOrFail($jwt);

        $this->assertSame(7, $decoded->userId);
        $this->assertFalse(property_exists($decoded, 'exp'));
    }
}
