<?php

namespace Devups\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base des tests e2e : client HTTP minimal (curl) avec jar de cookies,
 * contre le serveur demarre par HttpServer.
 */
abstract class HttpTestCase extends TestCase
{
    private static ?string $cookieJar = null;

    public static function setUpBeforeClass(): void
    {
        if (getenv('DEVUPS_TEST_SKIP_DB') === '1')
            self::markTestSkipped('DEVUPS_TEST_SKIP_DB=1');
        HttpServer::start();
        self::$cookieJar = tempnam(sys_get_temp_dir(), 'cj');
    }

    protected function setUp(): void
    {
        parent::setUp();
        HttpServer::newErrors(); // ignore ce qui precede ce test
    }

    /**
     * @return array{status:int, body:string, headers:array, location:?string}
     */
    protected function request(string $method, string $path, array $data = null, array $headers = [], bool $json = false): array
    {
        $ch = curl_init(HttpServer::baseUrl() . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_COOKIEJAR => self::$cookieJar,
            CURLOPT_COOKIEFILE => self::$cookieJar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($data !== null) {
            if ($json) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        if ($headers)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        if ($raw === false)
            $this->fail('requete HTTP impossible : ' . curl_error($ch));
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: null;
        curl_close($ch);

        return [
            'status' => $status,
            'headers' => explode("\r\n", trim(substr($raw, 0, $headerSize))),
            'body' => substr($raw, $headerSize),
            'location' => $location,
        ];
    }

    protected function get(string $path, array $headers = []): array
    {
        return $this->request('GET', $path, null, $headers);
    }

    /**
     * ETAPE L.2 : plusieurs requetes GET envoyees EN PARALLELE (curl_multi),
     * avec le MEME cookie jar (lu seul - CURLOPT_COOKIEFILE sans
     * CURLOPT_COOKIEJAR, pour ne pas faire courir N ecritures concurrentes
     * sur le meme fichier). Sert a verifier qu'une rafale de requetes sur
     * une session deja active ne provoque jamais de deconnexion.
     *
     * @return array<int, array{status:int, body:string}>
     */
    protected function parallelGet(array $paths): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($paths as $i => $path) {
            $ch = curl_init(HttpServer::baseUrl() . '/' . ltrim($path, '/'));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_COOKIEFILE => self::$cookieJar,
                CURLOPT_TIMEOUT => 30,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0)
                curl_multi_select($mh);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $i => $ch) {
            $raw = curl_multi_getcontent($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $results[$i] = [
                'status' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                'body' => substr((string)$raw, $headerSize),
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
    }

    protected function json(array $response): array
    {
        $data = json_decode($response['body'], true);
        $this->assertIsArray($data, "reponse JSON attendue, recu :\n" . substr($response['body'], 0, 500));
        return $data;
    }

    protected function assertNoServerError(string $context = ''): void
    {
        $errors = HttpServer::newErrors();
        $this->assertSame([], $errors, "erreurs PHP cote serveur $context :\n" . implode("\n", $errors));
    }

    protected function loginAdmin(string $login = 'dv_admin', string $password = 'admin'): array
    {
        return $this->request('POST', 'admin/devups/ModuleAdmin/dvups-admin/connexion-action', [
            'login' => $login,
            'password' => $password,
        ]);
    }

    /**
     * Pour les requetes qu'il faut construire a la main avec curl (upload
     * multipart via CURLFile, request() ne le permet pas) tout en restant
     * dans la MEME session (cookie jar) que loginAdmin()/get()/post().
     */
    protected function cookieJarPath(): string
    {
        return self::$cookieJar;
    }
}
