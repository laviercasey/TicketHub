<?php

use PHPUnit\Framework\TestCase;

class TicketHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Ticket')) {
            require_once INCLUDE_DIR . 'class.ticket.php';
        }
    }

    public function testGetShortFIOFullName(): void
    {
        $this->assertEquals('Иван Иванов', Ticket::GetShortFIO('Иван Иванов'));
    }

    public function testGetShortFIOSingleName(): void
    {
        $this->assertEquals('Admin', Ticket::GetShortFIO('Admin'));
    }

    public function testGetShortFIOEmptyString(): void
    {
        $this->assertEquals('', Ticket::GetShortFIO(''));
    }

    public function testGetShortFIOThreePartName(): void
    {
        $this->assertEquals('Иван Иванович', Ticket::GetShortFIO('Иван Иванович Петров'));
    }

    public function testGetRusStatusOpen(): void
    {
        $this->assertEquals('В работе', Ticket::GetRusStatus('open'));
        $this->assertEquals('В работе', Ticket::GetRusStatus('Open'));
        $this->assertEquals('В работе', Ticket::GetRusStatus('OPEN'));
    }

    public function testGetRusStatusClosed(): void
    {
        $this->assertEquals('Завершено', Ticket::GetRusStatus('closed'));
        $this->assertEquals('Завершено', Ticket::GetRusStatus('Closed'));
    }

    public function testGetRusStatusReopen(): void
    {
        $this->assertEquals('В работе', Ticket::GetRusStatus('reopen'));
    }

    public function testGetRusStatusUnknown(): void
    {
        $this->assertEquals('pending', Ticket::GetRusStatus('pending'));
        $this->assertEquals('custom', Ticket::GetRusStatus('custom'));
    }
}
