<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

final class ConfigurationTest extends DatabaseTestCase
{
    public function testSetCreatesEntryWithItsValueTypeAndComment(): void
    {
        \Configuration::set('PHPUNIT_KEY', 'hello', 'string', 'un commentaire');

        $cf = \Configuration::getbyattribut('_key', 'PHPUNIT_KEY');
        $this->assertTrue((bool)$cf->getId());
        // regression : set() ecrivait la CLE a la place de la valeur, du type et du commentaire
        $this->assertSame('hello', $cf->get_value());
        $this->assertSame('string', $cf->get_type());
        $this->assertSame('un commentaire', $cf->getComment());
    }

    public function testSetUpdatesExistingEntryWithoutDuplicating(): void
    {
        \Configuration::set('PHPUNIT_KEY', 'v1', 'string', 'c1');
        \Configuration::set('PHPUNIT_KEY', 'v2');

        $this->assertSame('v2', \Configuration::get('PHPUNIT_KEY'));
        $this->assertSame(1, $this->countRows('configuration', '_key = ?', ['PHPUNIT_KEY']));
        $this->assertSame('c1', \Configuration::getbyattribut('_key', 'PHPUNIT_KEY')->getComment());
    }

    public function testGetReturnsDefaultForUnknownKey(): void
    {
        $this->assertNull(\Configuration::get('PHPUNIT_UNKNOWN_' . uniqid()));
        $this->assertSame('dflt', \Configuration::get('PHPUNIT_UNKNOWN_' . uniqid(), 'dflt'));
    }
}
