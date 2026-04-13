<?php

use PHPUnit\Framework\TestCase;

class SysTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Sys')) {
            require_once INCLUDE_DIR . 'class.sys.php';
        }

        DatabaseMock::reset();
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('Sys'));
    }

    public function testGetConfigReturnsNullWithoutDb(): void
    {
        $cfg = Sys::getConfig();
        $this->assertNull($cfg);
    }

    public function testGetConfigLoadsConfig(): void
    {
        DatabaseMock::setQueryResult(CONFIG_TABLE, [[
            'id' => 1,
            'isonline' => 1,
            'timezone_offset' => 0,
            'staff_ip_binding' => 0,
            'staff_max_logins' => 4,
            'staff_login_timeout' => 2,
            'staff_session_timeout' => 30,
            'client_max_logins' => 4,
            'client_login_timeout' => 2,
            'client_session_timeout' => 30,
            'max_page_size' => 25,
            'log_level' => 2,
            'log_graceperiod' => 3,
            'max_open_tickets' => 0,
            'autolock_minutes' => 3,
            'overdue_grace_period' => 0,
            'alert_email_id' => 0,
            'default_email_id' => 0,
            'default_dept_id' => 0,
            'default_priority_id' => 2,
            'default_template_id' => 0,
            'default_smtp_id' => 0,
            'spoof_default_smtp' => 0,
            'clicks_to_overdue' => 1,
            'use_email_priority' => 0,
            'enable_captcha' => 0,
            'enable_auto_cron' => 0,
            'enable_mail_fetch' => 0,
            'enable_kb' => 0,
            'enable_premade' => 1,
            'show_assigned_tickets' => 0,
            'show_answered_tickets' => 0,
            'hide_staff_name' => 0,
            'strip_quoted_reply' => 1,
            'reply_separator' => '-- reply above this line --',
            'pipe_token' => '',
            'random_ticket_ids' => 1,
            'helpdesk_title' => 'Test',
            'helpdesk_url' => 'http://test.local',
            'admin_email' => 'admin@test.local',
            'thversion' => '1.0',
            'api_whitelist' => '',
            'enable_daylight_saving' => 0,
            'allowed_filetypes' => '.doc,.pdf',
            'max_file_size' => 1048576,
            'allow_email_attachments' => 1,
            'allow_online_attachments' => 1,
            'allow_attachments' => 1,
            'updated' => '2026-01-01',
            'api_log_retention_days' => 30,
        ]]);

        $cfg = Sys::getConfig();
        $this->assertNotNull($cfg);
        $this->assertEquals(1, $cfg->getId());
    }

    public function testLogLevelMapping(): void
    {
        $sys = new Sys();
        $this->assertIsArray($sys->loglevel);
        $this->assertEquals('Error', $sys->loglevel[1]);
        $this->assertEquals('Warning', $sys->loglevel[2]);
        $this->assertEquals('Debug', $sys->loglevel[3]);
    }

    public function testPurgeLogsNoConfig(): void
    {
        $GLOBALS['cfg'] = null;
        Sys::purgeLogs();
        $queries = DatabaseMock::getExecutedQueries();
        $deleteFound = false;
        foreach ($queries as $q) {
            if (stripos($q, 'DELETE') !== false && stripos($q, SYSLOG_TABLE) !== false) {
                $deleteFound = true;
            }
        }
        $this->assertFalse($deleteFound);
    }

    public function testPurgeLogsWithConfig(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getLogGraceperiod() { return 6; }
        };

        Sys::purgeLogs();
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('DELETE', $lastQuery);
        $this->assertStringContainsString(SYSLOG_TABLE, $lastQuery);
        $this->assertStringContainsString('6 MONTH', $lastQuery);

        unset($GLOBALS['cfg']);
    }

    public function testPurgeLogsGracePeriodNotNumeric(): void
    {
        $GLOBALS['cfg'] = new class {
            public function getLogGraceperiod() { return 'abc'; }
        };

        Sys::purgeLogs();
        $queries = DatabaseMock::getExecutedQueries();
        $deleteFound = false;
        foreach ($queries as $q) {
            if (stripos($q, 'DELETE') !== false && stripos($q, SYSLOG_TABLE) !== false) {
                $deleteFound = true;
            }
        }
        $this->assertFalse($deleteFound);

        unset($GLOBALS['cfg']);
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists('Sys', 'log'));
    }

    public function testAlertAdminMethodExists(): void
    {
        $this->assertTrue(method_exists('Sys', 'alertAdmin'));
    }
}
