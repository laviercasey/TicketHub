<?php

use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    private array $ticketRow;

    protected function setUp(): void
    {
        if (!class_exists('Ticket')) {
            require_once INCLUDE_DIR . 'class.ticket.php';
        }

        $this->ticketRow = [
            'ticket_id' => 1,
            'ticketID' => 'ABC123',
            'dept_id' => 1,
            'priority_id' => 2,
            'topic_id' => 1,
            'topicId' => 1,
            'staff_id' => 0,
            'andstaffs_id' => '',
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'subject' => 'Test Subject',
            'helptopic' => 'General',
            'status' => 'open',
            'source' => 'Web',
            'phone' => '+79991234567',
            'phone_ext' => '',
            'ip_address' => '127.0.0.1',
            'created' => '2026-01-15 10:00:00',
            'updated' => '2026-01-15 12:00:00',
            'closed' => null,
            'reopened' => null,
            'duedate' => '2026-02-15 10:00:00',
            'lastmessagedate' => '2026-01-15 10:00:00',
            'lastresponsedate' => null,
            'isoverdue' => 0,
            'isanswered' => 0,
            'lock_id' => 0,
            'priority_desc' => 'Normal',
            'dept_name' => 'Support',
            'staff_name' => '',
        ];

        DatabaseMock::reset();
    }

    private function setupTicketLookup(array $overrides = []): void
    {
        $row = array_merge($this->ticketRow, $overrides);
        DatabaseMock::setQueryResult('ticket.*', [$row]);
    }

    public function testLoadTicketById(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals(1, $ticket->getId());
    }

    public function testLoadTicketNotFound(): void
    {
        $ticket = new Ticket(999);
        $this->assertNull($ticket->getId());
    }

    public function testGetExtId(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('ABC123', $ticket->getExtId());
    }

    public function testGetEmail(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('ivan@example.com', $ticket->getEmail());
    }

    public function testGetName(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('Иван Иванов', $ticket->getName());
    }

    public function testGetSubject(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('Test Subject', $ticket->getSubject());
    }

    public function testGetStatus(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('open', $ticket->getStatus());
    }

    public function testGetDeptId(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals(1, $ticket->getDeptId());
    }

    public function testGetDeptName(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('Support', $ticket->getDeptName());
    }

    public function testGetTopicId(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals(1, $ticket->getTopicId());
    }

    public function testGetPriorityId(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals(2, $ticket->getPriorityId());
    }

    public function testGetPhone(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('+79991234567', $ticket->getPhone());
    }

    public function testGetSource(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('Web', $ticket->getSource());
    }

    public function testGetIP(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('127.0.0.1', $ticket->getIP());
    }

    public function testIsOpen(): void
    {
        $this->setupTicketLookup(['status' => 'open']);
        $ticket = new Ticket(1);
        $this->assertTrue($ticket->isOpen());
    }

    public function testIsClosed(): void
    {
        $this->setupTicketLookup(['status' => 'closed']);
        $ticket = new Ticket(1);
        $this->assertTrue($ticket->isClosed());
    }

    public function testIsNotOpen(): void
    {
        $this->setupTicketLookup(['status' => 'closed']);
        $ticket = new Ticket(1);
        $this->assertFalse($ticket->isOpen());
    }

    public function testIsOverdue(): void
    {
        $this->setupTicketLookup(['isoverdue' => 1]);
        $ticket = new Ticket(1);
        $this->assertTrue($ticket->isOverdue());
    }

    public function testIsNotOverdue(): void
    {
        $this->setupTicketLookup(['isoverdue' => 0]);
        $ticket = new Ticket(1);
        $this->assertFalse($ticket->isOverdue());
    }

    public function testGetStaffId(): void
    {
        $this->setupTicketLookup(['staff_id' => 5]);
        $ticket = new Ticket(1);
        $this->assertEquals(5, $ticket->getStaffId());
    }

    public function testIsAssigned(): void
    {
        $this->setupTicketLookup(['staff_id' => 5]);
        $ticket = new Ticket(1);
        $this->assertTrue($ticket->isAssigned());
    }

    public function testIsNotAssigned(): void
    {
        $this->setupTicketLookup(['staff_id' => 0]);
        $ticket = new Ticket(1);
        $this->assertFalse($ticket->isAssigned());
    }

    public function testGetCreateDate(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('2026-01-15 10:00:00', $ticket->getCreateDate());
    }

    public function testIsLocked(): void
    {
        $this->setupTicketLookup(['lock_id' => 5]);
        $ticket = new Ticket(1);
        $this->assertTrue($ticket->isLocked());
    }

    public function testIsNotLocked(): void
    {
        $this->setupTicketLookup(['lock_id' => 0]);
        $ticket = new Ticket(1);
        $this->assertFalse($ticket->isLocked());
    }

    public function testGetDueDate(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('2026-02-15 10:00:00', $ticket->getDueDate());
    }

    public function testGetHelptopic(): void
    {
        $this->setupTicketLookup(['topicId' => 0]);
        $ticket = new Ticket(1);
        $this->assertEquals('General', $ticket->getHelptopic());
    }

    public function testGetPriority(): void
    {
        $this->setupTicketLookup();
        $ticket = new Ticket(1);
        $this->assertEquals('Normal', $ticket->getPriority());
    }

    public function testGetRusStatusOpen(): void
    {
        $this->assertEquals('В работе', Ticket::GetRusStatus('open'));
    }

    public function testGetRusStatusClosed(): void
    {
        $this->assertEquals('Завершено', Ticket::GetRusStatus('closed'));
    }

    public function testGetRusStatusReopen(): void
    {
        $this->assertEquals('В работе', Ticket::GetRusStatus('reopen'));
    }

    public function testGetRusStatusCaseInsensitive(): void
    {
        $this->assertEquals('В работе', Ticket::GetRusStatus('OPEN'));
        $this->assertEquals('Завершено', Ticket::GetRusStatus('Closed'));
    }

    public function testGetRusStatusUnknown(): void
    {
        $this->assertEquals('pending', Ticket::GetRusStatus('pending'));
    }

    public function testGetShortFIOTwoParts(): void
    {
        $this->assertEquals('Иван Иванов', Ticket::GetShortFIO('Иван Иванов'));
    }

    public function testGetShortFIOSinglePart(): void
    {
        $this->assertEquals('Admin', Ticket::GetShortFIO('Admin'));
    }

    public function testGetShortFIOEmpty(): void
    {
        $this->assertEquals('', Ticket::GetShortFIO(''));
    }

    public function testGetShortFIOThreeParts(): void
    {
        $this->assertEquals('Иван Иванович', Ticket::GetShortFIO('Иван Иванович Петров'));
    }

    public function testGenExtRandID(): void
    {
        DatabaseMock::setQueryResult('SELECT ticket_id FROM', []);
        $id = Ticket::genExtRandID();
        $this->assertNotEmpty($id);
        $this->assertEquals(EXT_TICKET_ID_LEN, strlen($id));
    }

    public function testCreateValidationNoEmail(): void
    {
        $errors = [];
        $result = Ticket::create([
            'name' => 'Test',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'topicId' => 1,
            'deptId' => 1,
            'pri' => 2,
        ], $errors, 'Web');
        $this->assertEmpty($result);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = Ticket::create([
            'email' => 'test@test.com',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'topicId' => 1,
            'deptId' => 1,
            'pri' => 2,
        ], $errors, 'Web');
        $this->assertEmpty($result);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testCreateValidationNoSubject(): void
    {
        $errors = [];
        $result = Ticket::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'message' => 'Test message',
            'topicId' => 1,
            'deptId' => 1,
            'pri' => 2,
        ], $errors, 'Web');
        $this->assertEmpty($result);
        $this->assertArrayHasKey('subject', $errors);
    }

    public function testCreateValidationNoMessage(): void
    {
        $errors = [];
        $result = Ticket::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'subject' => 'Test Subject',
            'topicId' => 1,
            'deptId' => 1,
            'pri' => 2,
        ], $errors, 'Web');
        $this->assertEmpty($result);
        $this->assertArrayHasKey('message', $errors);
    }

    public function testCreateValidationInvalidEmail(): void
    {
        $errors = [];
        $result = Ticket::create([
            'name' => 'Test',
            'email' => 'not-an-email',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'topicId' => 1,
            'deptId' => 1,
            'pri' => 2,
        ], $errors, 'Web');
        $this->assertEmpty($result);
        $this->assertArrayHasKey('email', $errors);
    }
}
