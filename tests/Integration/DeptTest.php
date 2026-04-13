<?php

use PHPUnit\Framework\TestCase;

class DeptTest extends TestCase
{
    private array $deptRow;

    protected function setUp(): void
    {
        if (!class_exists('Dept')) {
            if (class_exists('Dept')) return;
            require_once INCLUDE_DIR . 'class.dept.php';
        }

        $this->deptRow = [
            'dept_id' => 1,
            'dept_name' => 'Support',
            'dept_signature' => 'Support Team',
            'dept_email' => 'support@example.com',
            'tpl_id' => 1,
            'email_id' => 1,
            'autoresp_email_id' => 0,
            'manager_id' => 0,
            'ispublic' => 1,
            'ticket_auto_response' => 1,
            'message_auto_response' => 1,
            'noreply_autoresp' => 0,
            'can_append_signature' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ];

        DatabaseMock::reset();
    }

    private function setupDeptLookup(array $overrides = []): void
    {
        $row = array_merge($this->deptRow, $overrides);
        DatabaseMock::setQueryResult('SELECT * FROM ' . DEPT_TABLE, [$row]);
    }

    public function testLoadDept(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertEquals(1, $dept->getId());
    }

    public function testLoadDeptNotFound(): void
    {
        $dept = new Dept(999);
        $this->assertEquals(0, $dept->getId());
    }

    public function testLoadDeptNoId(): void
    {
        $dept = new Dept(0);
        $this->assertEquals(0, $dept->getId());
    }

    public function testGetName(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertEquals('Support', $dept->getName());
    }

    public function testGetEmailId(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertEquals(1, $dept->getEmailId());
    }

    public function testGetTemplateId(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertEquals(1, $dept->getTemplateId());
    }

    public function testGetSignature(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertEquals('Support Team', $dept->getSignature());
    }

    public function testCanAppendSignature(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $this->assertTrue($dept->canAppendSignature());
    }

    public function testCannotAppendSignatureWhenDisabled(): void
    {
        $this->setupDeptLookup(['can_append_signature' => 0]);
        $dept = new Dept(1);
        $this->assertFalse($dept->canAppendSignature());
    }

    public function testCannotAppendSignatureWhenEmpty(): void
    {
        $this->setupDeptLookup(['dept_signature' => '']);
        $dept = new Dept(1);
        $this->assertFalse($dept->canAppendSignature());
    }

    public function testGetManagerId(): void
    {
        $this->setupDeptLookup(['manager_id' => 5]);
        $dept = new Dept(1);
        $this->assertEquals(5, $dept->getManagerId());
    }

    public function testIsPublic(): void
    {
        $this->setupDeptLookup(['ispublic' => 1]);
        $dept = new Dept(1);
        $this->assertTrue($dept->isPublic());
    }

    public function testIsPrivate(): void
    {
        $this->setupDeptLookup(['ispublic' => 0]);
        $dept = new Dept(1);
        $this->assertFalse($dept->isPublic());
    }

    public function testAutoRespONNewTicket(): void
    {
        $this->setupDeptLookup(['ticket_auto_response' => 1]);
        $dept = new Dept(1);
        $this->assertTrue($dept->autoRespONNewTicket());
    }

    public function testAutoRespONNewMessage(): void
    {
        $this->setupDeptLookup(['message_auto_response' => 1]);
        $dept = new Dept(1);
        $this->assertTrue($dept->autoRespONNewMessage());
    }

    public function testNoreplyAutoResp(): void
    {
        $this->setupDeptLookup(['noreply_autoresp' => 1]);
        $dept = new Dept(1);
        $this->assertTrue($dept->noreplyAutoResp());
    }

    public function testGetInfo(): void
    {
        $this->setupDeptLookup();
        $dept = new Dept(1);
        $info = $dept->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('Support', $info['dept_name']);
    }

    public function testGetInfoByIdStatic(): void
    {
        $this->setupDeptLookup();
        $info = Dept::getInfoById(1);
        $this->assertIsArray($info);
        $this->assertEquals(1, $info['dept_id']);
    }

    public function testGetInfoByIdNotFound(): void
    {
        $info = Dept::getInfoById(999);
        $this->assertNull($info);
    }

    public function testGetIdByName(): void
    {
        DatabaseMock::setQueryResult('SELECT dept_id FROM ' . DEPT_TABLE . ' WHERE dept_name', [['dept_id' => 1]]);
        $id = Dept::getIdByName('Support');
        $this->assertEquals(1, $id);
    }

    public function testGetIdByNameNotFound(): void
    {
        $id = Dept::getIdByName('NonExistent');
        $this->assertEquals(0, $id);
    }

    public function testGetIdByEmail(): void
    {
        DatabaseMock::setQueryResult('SELECT dept_id FROM ' . DEPT_TABLE . ' WHERE dept_email', [['dept_id' => 1]]);
        $id = Dept::getIdByEmail('support@example.com');
        $this->assertEquals(1, $id);
    }

    public function testGetNameById(): void
    {
        DatabaseMock::setQueryResult('SELECT dept_name FROM ' . DEPT_TABLE, [['dept_name' => 'Support']]);
        $name = Dept::getNameById(1);
        $this->assertEquals('Support', $name);
    }

    public function testDeleteDefaultDeptBlocked(): void
    {
        $cfg = new class {
            public function getDefaultDeptId() { return 1; }
        };
        $GLOBALS['cfg'] = $cfg;

        $result = Dept::delete(1);
        $this->assertEquals(0, $result);
    }

    public function testDeleteNonDefaultDept(): void
    {
        $cfg = new class {
            public function getDefaultDeptId() { return 99; }
        };
        $GLOBALS['cfg'] = $cfg;

        DatabaseMock::setAffectedRows(1);
        $result = Dept::delete(1);
        $this->assertEquals(1, $result);
    }

    public function testDeleteNonExistentDept(): void
    {
        $cfg = new class {
            public function getDefaultDeptId() { return 99; }
        };
        $GLOBALS['cfg'] = $cfg;

        DatabaseMock::setAffectedRows(0);
        $result = Dept::delete(999);
        $this->assertEquals(0, $result);
    }

    public function testGetEmailAddressWithNoEmail(): void
    {
        $this->setupDeptLookup(['email_id' => 0]);
        $dept = new Dept(1);
        $this->assertNull($dept->getEmailAddress());
    }
}
