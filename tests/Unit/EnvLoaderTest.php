<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EnvLoaderTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->file, <<<ENV
# commentaire
APP_NAME="Mon projet"
SINGLE='entre apostrophes'
PLAIN=valeur # commentaire en fin de ligne
export EXPORTED=oui
EMPTY=
EQUALS=a=b=c
  SPACED  =  ok
ENV);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testParsesQuotedPlainAndExportedValues(): void
    {
        $values = dv_env_load($this->file);

        $this->assertSame('Mon projet', $values['APP_NAME']);
        $this->assertSame('entre apostrophes', $values['SINGLE']);
        $this->assertSame('valeur', $values['PLAIN']);
        $this->assertSame('oui', $values['EXPORTED']);
        $this->assertSame('', $values['EMPTY']);
        $this->assertSame('a=b=c', $values['EQUALS']);
        $this->assertSame('ok', $values['SPACED']);
        $this->assertArrayNotHasKey('# commentaire', $values);
    }

    public function testMissingFileGivesEmptyArray(): void
    {
        $this->assertSame([], dv_env_load('/nope/does-not-exist/.env'));
    }

    public function testProcessEnvironmentWinsAndValuesAreTyped(): void
    {
        putenv('DV_TEST_BOOL=true');
        putenv('DV_TEST_FALSE=false');
        putenv('DV_TEST_NULL=null');
        putenv('DV_TEST_STR=hello');
        try {
            $this->assertTrue(dv_env('DV_TEST_BOOL'));
            $this->assertFalse(dv_env('DV_TEST_FALSE'));
            $this->assertNull(dv_env('DV_TEST_NULL', 'default'));
            $this->assertSame('hello', dv_env('DV_TEST_STR'));
            $this->assertSame('fallback', dv_env('DV_TEST_UNDEFINED_' . uniqid(), 'fallback'));
        } finally {
            putenv('DV_TEST_BOOL');
            putenv('DV_TEST_FALSE');
            putenv('DV_TEST_NULL');
            putenv('DV_TEST_STR');
        }
    }

    public function testConstantsComeFromTheEnvironment(): void
    {
        // phpunit.xml.dist pilote ces valeurs : le noyau doit les avoir lues
        $this->assertSame(getenv('DB_NAME'), dbname);
        $this->assertSame(getenv('APP_NAME'), PROJECT_NAME);
        $this->assertStringEndsWith('/', __env);
    }
}
