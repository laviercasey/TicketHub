<?php

use PHPUnit\Framework\TestCase;

class BanlistTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Banlist')) {
            require_once INCLUDE_DIR . 'class.banlist.php';
        }
        DatabaseMock::reset();
    }

    public function testAddEmail(): void
    {
        DatabaseMock::setLastInsertId(1);
        $result = Banlist::add('banned@example.com', 'admin');
        $this->assertEquals(1, $result);
    }

    public function testAddEmailFailure(): void
    {
        DatabaseMock::setLastInsertId(0);
        $result = Banlist::add('test@example.com');
        $this->assertEquals(0, $result);
    }

    public function testAddEmailRecordsQuery(): void
    {
        DatabaseMock::setLastInsertId(1);
        Banlist::add('test@example.com', 'admin');

        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('INSERT IGNORE INTO', $lastQuery);
        $this->assertStringContainsString(BANLIST_TABLE, $lastQuery);
    }

    public function testRemoveEmail(): void
    {
        DatabaseMock::setAffectedRows(1);
        $result = Banlist::remove('banned@example.com');
        $this->assertTrue($result);
    }

    public function testRemoveEmailNotFound(): void
    {
        DatabaseMock::setAffectedRows(0);
        $result = Banlist::remove('nonexistent@example.com');
        $this->assertFalse($result);
    }

    public function testRemoveEmailRecordsQuery(): void
    {
        DatabaseMock::setAffectedRows(1);
        Banlist::remove('test@example.com');

        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('DELETE FROM', $lastQuery);
        $this->assertStringContainsString(BANLIST_TABLE, $lastQuery);
    }

    public function testIsBannedTrue(): void
    {
        DatabaseMock::setQueryResult('SELECT id FROM ' . BANLIST_TABLE, [['id' => 1]]);
        $this->assertTrue(Banlist::isbanned('banned@example.com'));
    }

    public function testIsBannedFalse(): void
    {
        $this->assertFalse(Banlist::isbanned('clean@example.com'));
    }
}
