<?php

use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Email')) {
            require_once INCLUDE_DIR . 'class.email.php';
        }

        DatabaseMock::reset();
    }

    private function makeEmailRow(array $overrides = []): array
    {
        return array_merge([
            'email_id' => 1,
            'email' => 'support@example.com',
            'name' => 'Support',
            'dept_id' => 1,
            'priority_id' => 2,
            'noautoresp' => 0,
            'smtp_active' => 0,
            'smtp_host' => '',
            'smtp_port' => 25,
            'smtp_auth' => 0,
            'userid' => '',
            'userpass' => '',
            'mail_active' => 0,
            'mail_host' => '',
            'mail_port' => 110,
            'mail_protocol' => 'POP',
            'mail_encryption' => '',
            'mail_fetchfreq' => 5,
            'mail_fetchmax' => 30,
            'mail_delete' => 0,
            'mail_errors' => 0,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupEmailLookup(array $overrides = []): void
    {
        $row = $this->makeEmailRow($overrides);
        DatabaseMock::setQueryResult(EMAIL_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupEmailLookup();
        $email = new Email(1);
        $this->assertEquals(1, $email->getId());
    }

    public function testConstructorNotFound(): void
    {
        $email = new Email(999);
        $this->assertEquals(0, $email->getId());
    }

    public function testConstructorZeroId(): void
    {
        $email = new Email(0);
        $this->assertEquals(0, $email->getId());
    }

    public function testConstructorNoFetch(): void
    {
        $email = new Email(5, false);
        $this->assertEquals(5, $email->id);
    }

    public function testGetEmail(): void
    {
        $this->setupEmailLookup();
        $email = new Email(1);
        $this->assertEquals('support@example.com', $email->getEmail());
    }

    public function testGetName(): void
    {
        $this->setupEmailLookup();
        $email = new Email(1);
        $this->assertEquals('Support', $email->getName());
    }

    public function testGetAddressWithName(): void
    {
        $this->setupEmailLookup(['name' => 'Help Desk', 'email' => 'help@example.com']);
        $email = new Email(1);
        $this->assertEquals('Help Desk<help@example.com>', $email->getAddress());
    }

    public function testGetAddressWithoutName(): void
    {
        $this->setupEmailLookup(['name' => '', 'email' => 'noreply@example.com']);
        $email = new Email(1);
        $this->assertEquals('noreply@example.com', $email->getAddress());
    }

    public function testGetDeptId(): void
    {
        $this->setupEmailLookup(['dept_id' => 3]);
        $email = new Email(1);
        $this->assertEquals(3, $email->getDeptId());
    }

    public function testGetPriorityId(): void
    {
        $this->setupEmailLookup(['priority_id' => 4]);
        $email = new Email(1);
        $this->assertEquals(4, $email->getPriorityId());
    }

    public function testAutoRespondEnabled(): void
    {
        $this->setupEmailLookup(['noautoresp' => 0]);
        $email = new Email(1);
        $this->assertTrue($email->autoRespond());
    }

    public function testAutoRespondDisabled(): void
    {
        $this->setupEmailLookup(['noautoresp' => 1]);
        $email = new Email(1);
        $this->assertFalse($email->autoRespond());
    }

    public function testGetInfo(): void
    {
        $this->setupEmailLookup();
        $email = new Email(1);
        $info = $email->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('support@example.com', $info['email']);
    }

    public function testIsSMTPEnabledFalse(): void
    {
        $this->setupEmailLookup(['smtp_active' => 0]);
        $email = new Email(1);
        $this->assertFalse((bool) $email->isSMTPEnabled());
    }

    public function testIsSMTPEnabledTrue(): void
    {
        $this->setupEmailLookup(['smtp_active' => 1]);
        $email = new Email(1);
        $this->assertTrue((bool) $email->isSMTPEnabled());
    }

    public function testGetSMTPInfoWhenInactive(): void
    {
        $this->setupEmailLookup(['smtp_active' => 0]);
        $email = new Email(1);
        $info = $email->getSMTPInfo(true);
        $this->assertEmpty($info);
    }

    public function testGetSMTPInfoWhenActiveAll(): void
    {
        $this->setupEmailLookup([
            'smtp_active' => 1,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_auth' => 1,
            'userid' => 'user@example.com',
            'userpass' => Misc::encrypt('password123', SECRET_SALT),
        ]);
        $email = new Email(1);
        $info = $email->getSMTPInfo(false);
        $this->assertArrayHasKey('host', $info);
        $this->assertArrayHasKey('port', $info);
        $this->assertArrayHasKey('auth', $info);
        $this->assertArrayHasKey('username', $info);
        $this->assertArrayHasKey('password', $info);
        $this->assertEquals('smtp.example.com', $info['host']);
    }

    public function testGetIdByEmailFound(): void
    {
        DatabaseMock::setQueryResult(EMAIL_TABLE, [['email_id' => 7]]);
        $id = Email::getIdByEmail('support@example.com');
        $this->assertEquals(7, $id);
    }

    public function testGetIdByEmailNotFound(): void
    {
        $id = Email::getIdByEmail('nobody@example.com');
        $this->assertNull($id);
    }

    public function testSaveValidationNoEmail(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getAdminEmail() { return 'admin@test.local'; }
        };

        $errors = [];
        $result = Email::save(0, [
            'email' => '',
            'dept_id' => 1,
            'priority_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);

        unset($GLOBALS['cfg']);
    }

    public function testSaveValidationInvalidEmail(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getAdminEmail() { return 'admin@test.local'; }
        };

        $errors = [];
        $result = Email::save(0, [
            'email' => 'not-valid',
            'dept_id' => 1,
            'priority_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);

        unset($GLOBALS['cfg']);
    }

    public function testSaveValidationNoDept(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getAdminEmail() { return 'admin@test.local'; }
        };

        $errors = [];
        $result = Email::save(0, [
            'email' => 'test@example.com',
            'dept_id' => 0,
            'priority_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('dept_id', $errors);

        unset($GLOBALS['cfg']);
    }

    public function testSaveValidationNoPriority(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getAdminEmail() { return 'admin@test.local'; }
        };

        $errors = [];
        $result = Email::save(0, [
            'email' => 'test@example.com',
            'dept_id' => 1,
            'priority_id' => 0,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('priority_id', $errors);

        unset($GLOBALS['cfg']);
    }

    public function testSaveValidationSmtpNoHost(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getAdminEmail() { return 'admin@test.local'; }
        };

        $errors = [];
        Email::save(0, [
            'email' => 'test@example.com',
            'dept_id' => 1,
            'priority_id' => 1,
            'smtp_active' => 1,
            'smtp_host' => '',
            'smtp_port' => 0,
            'smtp_auth' => 0,
            'userid' => '',
            'userpass' => '',
        ], $errors);
        $this->assertArrayHasKey('smtp_host', $errors);

        unset($GLOBALS['cfg']);
    }
}
