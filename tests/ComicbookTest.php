<?php

namespace tests;
use Comicbook;

class ComicbookTest extends \PHPUnit\Framework\TestCase
{
    public function testBookTitle()
    {
        $book = new Comicbook();
        $book->setTitle("Test Book");
        $this->assertEquals("Test Book", $book->name);
    }

}