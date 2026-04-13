<?php

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private Config $config;
    private array $configRow;

    protected function setUp(): void
    {
        if (!class_exists('Email')) {
            eval('class Email {
                public $id;
                public $address;
                public function __construct($id) { $this->id = $id; $this->address = "test@test.com"; }
                public function getAddress() { return $this->address; }
                public static function getIdByEmail($email) { return 0; }
            }');
        }
        if (!class_exists('Config')) {
            require_once INCLUDE_DIR . 'class.config.php';
        }

        $this->configRow = [
            'id' => 1,
            'isonline' => 1,
            'pipe_token' => 'test-pipe-token-abc123',
            'thversion' => '1.0',
            'enable_daylight_saving' => 1,
            'time_format' => 'h:i A',
            'date_format' => 'm/d/Y',
            'datetime_format' => 'm/d/Y h:i A',
            'daydatetime_format' => 'D, m/d/Y h:i A',
            'helpdesk_title' => 'TicketHub',
            'helpdesk_url' => 'https://support.example.com/',
            'timezone_offset' => '+03:00',
            'max_page_size' => 25,
            'overdue_grace_period' => 0,
            'client_session_timeout' => 30,
            'client_login_timeout' => 5,
            'client_max_logins' => 3,
            'staff_session_timeout' => 60,
            'staff_login_timeout' => 5,
            'staff_max_logins' => 5,
            'autolock_minutes' => 3,
            'default_dept_id' => 1,
            'default_email_id' => 1,
            'alert_email_id' => 2,
            'default_smtp_id' => 0,
            'spoof_default_smtp' => 0,
            'default_priority_id' => 2,
            'default_template_id' => 1,
            'max_open_tickets' => 0,
            'max_file_size' => 1048576,
            'log_level' => 2,
            'log_graceperiod' => 24,
            'log_ticket_activity' => 1,
            'clickable_urls' => 1,
            'enable_mail_fetch' => 0,
            'staff_ip_binding' => 0,
            'enable_captcha' => 0,
            'enable_auto_cron' => 0,
            'enable_email_piping' => 0,
            'allow_priority_change' => 0,
            'use_email_priority' => 0,
            'admin_email' => 'admin@example.com',
            'reply_separator' => '-- reply above this line --',
            'strip_quoted_reply' => 1,
            'random_ticket_ids' => 1,
            'ticket_autoresponder' => 1,
            'message_autoresponder' => 1,
            'ticket_notice_active' => 1,
            'message_alert_active' => 1,
            'message_alert_laststaff' => 1,
            'message_alert_assigned' => 1,
            'message_alert_dept_manager' => 0,
            'note_alert_active' => 1,
            'note_alert_laststaff' => 1,
            'note_alert_assigned' => 1,
            'note_alert_dept_manager' => 0,
            'ticket_alert_active' => 1,
            'ticket_alert_admin' => 1,
            'ticket_alert_dept_manager' => 1,
            'ticket_alert_dept_members' => 0,
            'overdue_alert_active' => 1,
            'overdue_alert_assigned' => 1,
            'overdue_alert_dept_manager' => 1,
            'overdue_alert_dept_members' => 0,
            'auto_assign_reopened_tickets' => 1,
            'show_assigned_tickets' => 1,
            'show_answered_tickets' => 1,
            'hide_staff_name' => 0,
            'overlimit_notice_active' => 0,
            'send_sql_errors' => 1,
            'send_login_errors' => 1,
            'send_mailparse_errors' => 1,
            'email_attachments' => 1,
            'allow_attachments' => 1,
            'allow_online_attachments' => 1,
            'allow_online_attachments_onlogin' => 1,
            'allow_email_attachments' => 1,
            'upload_dir' => '/tmp/uploads',
            'allowed_filetypes' => '.jpg,.png,.pdf',
        ];

        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT * FROM ' . CONFIG_TABLE, [$this->configRow]);
    }

    private function createConfig(): Config
    {
        return new Config(1);
    }

    public function testLoadConfigById(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1, $config->getId());
    }

    public function testLoadInvalidId(): void
    {
        DatabaseMock::reset();
        $config = new Config(0);
        $this->assertEmpty($config->config);
    }

    public function testIsHelpDeskOnline(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->isHelpDeskOffline());
    }

    public function testIsHelpDeskOffline(): void
    {
        $this->configRow['isonline'] = 0;
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT * FROM ' . CONFIG_TABLE, [$this->configRow]);
        $config = $this->createConfig();
        $this->assertTrue($config->isHelpDeskOffline());
    }

    public function testGetPipeToken(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('test-pipe-token-abc123', $config->getPipeToken());
    }

    public function testGetVersion(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('1.0', $config->getVersion());
    }

    public function testGetTitle(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('TicketHub', $config->getTitle());
    }

    public function testGetUrl(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('https://support.example.com/', $config->getUrl());
    }

    public function testGetBaseUrl(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('https://support.example.com', $config->getBaseUrl());
    }

    public function testGetTimeFormat(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('h:i A', $config->getTimeFormat());
    }

    public function testGetDateFormat(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('m/d/Y', $config->getDateFormat());
    }

    public function testGetDateTimeFormat(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('m/d/Y h:i A', $config->getDateTimeFormat());
    }

    public function testGetDayDateTimeFormat(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('D, m/d/Y h:i A', $config->getDayDateTimeFormat());
    }

    public function testGetTZOffset(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('+03:00', $config->getTZOffset());
    }

    public function testGetPageSize(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(25, $config->getPageSize());
    }

    public function testGetGracePeriod(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(0, $config->getGracePeriod());
    }

    public function testGetClientSessionTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1800, $config->getClientSessionTimeout());
    }

    public function testGetClientTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1800, $config->getClientTimeout());
    }

    public function testGetClientLoginTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(300, $config->getClientLoginTimeout());
    }

    public function testGetClientMaxLogins(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(3, $config->getClientMaxLogins());
    }

    public function testGetStaffSessionTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(3600, $config->getStaffSessionTimeout());
    }

    public function testGetStaffTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(3600, $config->getStaffTimeout());
    }

    public function testGetStaffLoginTimeout(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(300, $config->getStaffLoginTimeout());
    }

    public function testGetStaffMaxLogins(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(5, $config->getStaffMaxLogins());
    }

    public function testGetLockTime(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(3, $config->getLockTime());
    }

    public function testGetDefaultDeptId(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1, $config->getDefaultDeptId());
    }

    public function testGetDefaultEmailId(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1, $config->getDefaultEmailId());
    }

    public function testGetAlertEmailId(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(2, $config->getAlertEmailId());
    }

    public function testGetDefaultPriorityId(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(2, $config->getDefaultPriorityId());
    }

    public function testGetDefaultTemplateId(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1, $config->getDefaultTemplateId());
    }

    public function testGetMaxOpenTickets(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(0, $config->getMaxOpenTickets());
    }

    public function testGetMaxFileSize(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1048576, $config->getMaxFileSize());
    }

    public function testGetLogLevel(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(2, $config->getLogLevel());
    }

    public function testGetLogGracePeriod(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(24, $config->getLogGracePeriod());
    }

    public function testLogTicketActivity(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(1, $config->logTicketActivity());
    }

    public function testClickableURLS(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->clickableURLS());
    }

    public function testCanFetchMail(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->canFetchMail());
    }

    public function testEnableStaffIPBinding(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->enableStaffIPBinding());
    }

    public function testEnableAutoCron(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->enableAutoCron());
    }

    public function testEnableEmailPiping(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->enableEmailPiping());
    }

    public function testAllowPriorityChange(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->allowPriorityChange());
    }

    public function testUseEmailPriority(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->useEmailPriority());
    }

    public function testGetAdminEmail(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('admin@example.com', $config->getAdminEmail());
    }

    public function testGetReplySeparator(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('-- reply above this line --', $config->getReplySeparator());
    }

    public function testStripQuotedReply(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->stripQuotedReply());
    }

    public function testSaveEmailHeaders(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->saveEmailHeaders());
    }

    public function testUseRandomIds(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->useRandomIds());
    }

    public function testAutoRespONNewTicket(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->autoRespONNewTicket());
    }

    public function testAutoRespONNewMessage(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->autoRespONNewMessage());
    }

    public function testNotifyONNewStaffTicket(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->notifyONNewStaffTicket());
    }

    public function testAlertONNewMessage(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONNewMessage());
    }

    public function testAlertONNewNote(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONNewNote());
    }

    public function testAlertONNewTicket(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONNewTicket());
    }

    public function testAlertONOverdueTicket(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONOverdueTicket());
    }

    public function testAutoAssignReopenedTickets(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->autoAssignReopenedTickets());
    }

    public function testShowAssignedTickets(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->showAssignedTickets());
    }

    public function testShowAnsweredTickets(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->showAnsweredTickets());
    }

    public function testHideStaffName(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->hideStaffName());
    }

    public function testSendOverLimitNotice(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->sendOverLimitNotice());
    }

    public function testAlertONSQLError(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONSQLError());
    }

    public function testAlertONLoginError(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONLoginError());
    }

    public function testAlertONMailParseError(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertONMailParseError());
    }

    public function testEmailAttachments(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->emailAttachments());
    }

    public function testAllowAttachments(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->allowAttachments());
    }

    public function testAllowOnlineAttachments(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->allowOnlineAttachments());
    }

    public function testAllowAttachmentsOnlogin(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->allowAttachmentsOnlogin());
    }

    public function testAllowEmailAttachments(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->allowEmailAttachments());
    }

    public function testGetUploadDir(): void
    {
        $config = $this->createConfig();
        $this->assertEquals('/tmp/uploads', $config->getUploadDir());
    }

    public function testCanUploadFileTypeAllowed(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->canUploadFileType('document.jpg'));
        $this->assertTrue($config->canUploadFileType('image.png'));
        $this->assertTrue($config->canUploadFileType('file.pdf'));
    }

    public function testCanUploadFileTypeNotAllowed(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->canUploadFileType('script.exe'));
        $this->assertFalse($config->canUploadFileType('virus.bat'));
    }

    public function testGetConfig(): void
    {
        $config = $this->createConfig();
        $this->assertIsArray($config->getConfig());
        $this->assertArrayHasKey('helpdesk_title', $config->getConfig());
    }

    public function testObserveDaylightSaving(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->observeDaylightSaving());
    }

    public function testSetMysqlTZSystem(): void
    {
        $config = $this->createConfig();
        $config->setMysqlTZ('SYSTEM');
        $offset = $config->getMysqlTZoffset();
        $this->assertNotNull($offset);
    }

    public function testSetMysqlTZWithOffset(): void
    {
        $config = $this->createConfig();
        $config->setMysqlTZ('+03:00');
        $this->assertEquals('+03', $config->getMysqlTZoffset());
    }

    public function testAllowSMTPSpoofing(): void
    {
        $config = $this->createConfig();
        $this->assertEquals(0, $config->allowSMTPSpoofing());
    }

    public function testAlertLastRespondentONNewMessage(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertLastRespondentONNewMessage());
    }

    public function testAlertAssignedONNewMessage(): void
    {
        $config = $this->createConfig();
        $this->assertTrue($config->alertAssignedONNewMessage());
    }

    public function testAlertDeptManagerONNewMessage(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->alertDeptManagerONNewMessage());
    }

    public function testAlertDeptMembersONNewTicket(): void
    {
        $config = $this->createConfig();
        $this->assertFalse($config->alertDeptMembersONNewTicket());
    }
}
