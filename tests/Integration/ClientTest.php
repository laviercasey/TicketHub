<?php

use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Client')) {
            require_once INCLUDE_DIR . 'class.client.php';
        }

        DatabaseMock::reset();
    }

    private function makeClientRow(array $overrides = []): array
    {
        return array_merge([
            'ticket_id' => 1,
            'ticketID' => 'ABC123',
            'name' => 'john doe',
            'email' => 'john@example.com',
        ], $overrides);
    }

    public function testConstructorLoads(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertEquals('ABC123', $client->getId());
    }

    public function testConstructorNotFound(): void
    {
        $client = new Client('unknown@test.com', 'XYZ999');
        $this->assertEquals(0, $client->getId());
    }

    public function testIsClient(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertTrue($client->isClient());
    }

    public function testGetEmail(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertEquals('john@example.com', $client->getEmail());
    }

    public function testGetUserName(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertEquals('john@example.com', $client->getUserName());
    }

    public function testGetName(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertEquals('John doe', $client->getName());
    }

    public function testGetTicketID(): void
    {
        DatabaseMock::setQueryResult(TICKET_TABLE, [$this->makeClientRow()]);
        $client = new Client('john@example.com', 'ABC123');
        $this->assertEquals('ABC123', $client->getTicketID());
    }
}
