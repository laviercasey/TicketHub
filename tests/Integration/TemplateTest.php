<?php

use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Template')) {
            require_once INCLUDE_DIR . 'class.msgtpl.php';
        }

        DatabaseMock::reset();
    }

    private function makeTemplateRow(array $overrides = []): array
    {
        return array_merge([
            'tpl_id' => 1,
            'cfg_id' => 1,
            'name' => 'Default Template',
            'notes' => 'Default notes',
            'ticket_autoresp_subj' => 'Auto: #{ticket_number}',
            'ticket_autoresp_body' => 'Thank you for contacting us.',
            'message_autoresp_subj' => 'Re: #{ticket_number}',
            'message_autoresp_body' => 'Message received.',
            'ticket_notice_subj' => 'Notice: #{ticket_number}',
            'ticket_notice_body' => 'Notice body.',
            'ticket_alert_subj' => 'Alert: New ticket',
            'ticket_alert_body' => 'New ticket created.',
            'message_alert_subj' => 'Alert: New message',
            'message_alert_body' => 'New message received.',
            'note_alert_subj' => 'Alert: New note',
            'note_alert_body' => 'New note added.',
            'assigned_alert_subj' => 'Ticket assigned',
            'assigned_alert_body' => 'Ticket assigned to you.',
            'ticket_overdue_subj' => 'Overdue: #{ticket_number}',
            'ticket_overdue_body' => 'Ticket is overdue.',
            'ticket_overlimit_subj' => 'Over limit',
            'ticket_overlimit_body' => 'Ticket limit exceeded.',
            'ticket_reply_subj' => 'Re: #{ticket_number}',
            'ticket_reply_body' => 'Staff reply.',
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-15 10:30:00',
        ], $overrides);
    }

    private function setupTemplateLookup(array $overrides = []): void
    {
        $row = $this->makeTemplateRow($overrides);
        DatabaseMock::setQueryResult(EMAIL_TEMPLATE_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $this->assertEquals(1, $tpl->getId());
    }

    public function testConstructorNotFound(): void
    {
        $tpl = new Template(999);
        $this->assertEquals(0, $tpl->getId());
    }

    public function testConstructorZeroId(): void
    {
        $tpl = new Template(0);
        $this->assertEquals(0, $tpl->getId());
    }

    public function testGetName(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $this->assertEquals('Default Template', $tpl->getName());
    }

    public function testGetCfgId(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $this->assertEquals(1, $tpl->getCfgId());
    }

    public function testGetInfo(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $info = $tpl->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('Default Template', $info['name']);
    }

    public function testGetCreateDate(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $this->assertEquals('2026-01-01 00:00:00', $tpl->getCreateDate());
    }

    public function testGetUpdateDate(): void
    {
        $this->setupTemplateLookup();
        $tpl = new Template(1);
        $this->assertEquals('2026-01-15 10:30:00', $tpl->getUpdateDate());
    }

    public function testGetIdByNameFound(): void
    {
        DatabaseMock::setQueryResult(EMAIL_TEMPLATE_TABLE, [['tpl_id' => 3]]);
        $id = Template::getIdByName('Default Template');
        $this->assertEquals(3, $id);
    }

    public function testGetIdByNameNotFound(): void
    {
        $id = Template::getIdByName('Nonexistent');
        $this->assertEquals(0, $id);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = Template::create([
            'name' => '',
            'copy_template' => 1,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testCreateValidationNoCopyTemplate(): void
    {
        $errors = [];
        $result = Template::create([
            'name' => 'New Template',
            'copy_template' => 0,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('copy_template', $errors);
    }

    public function testConstructorWithCfgId(): void
    {
        $this->setupTemplateLookup(['cfg_id' => 5]);
        $tpl = new Template(1, 5);
        $this->assertEquals(1, $tpl->getId());
        $this->assertEquals(5, $tpl->getCfgId());
    }
}
