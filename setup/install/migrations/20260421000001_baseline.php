<?php
declare(strict_types=1);

class Baseline
{
    public function up(): bool
    {
        $prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : 'th_';

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}config` (
          `id` tinyint(1) unsigned NOT NULL auto_increment,
          `isonline` tinyint(1) unsigned NOT NULL default '0',
          `timezone_offset` float(3,1) NOT NULL default '0.0',
          `enable_daylight_saving` tinyint(1) unsigned NOT NULL default '0',
          `staff_ip_binding` tinyint(1) unsigned NOT NULL default '1',
          `staff_max_logins` tinyint(3) unsigned NOT NULL default '4',
          `staff_login_timeout` int(10) unsigned NOT NULL default '2',
          `staff_session_timeout` int(10) unsigned NOT NULL default '30',
          `client_max_logins` tinyint(3) unsigned NOT NULL default '4',
          `client_login_timeout` int(10) unsigned NOT NULL default '2',
          `client_session_timeout` int(10) unsigned NOT NULL default '30',
          `max_page_size` tinyint(3) unsigned NOT NULL default '25',
          `max_open_tickets` tinyint(3) unsigned NOT NULL default '0',
          `max_file_size` int(11) unsigned NOT NULL default '1048576',
          `autolock_minutes` tinyint(3) unsigned NOT NULL default '3',
          `overdue_grace_period` int(10) unsigned NOT NULL default '0',
          `alert_email_id` tinyint(4) unsigned NOT NULL default '0',
          `default_email_id` tinyint(4) unsigned NOT NULL default '0',
          `default_dept_id` tinyint(3) unsigned NOT NULL default '0',
          `default_priority_id` tinyint(2) unsigned NOT NULL default '2',
          `default_template_id` tinyint(4) unsigned NOT NULL default '1',
          `default_smtp_id` tinyint(4) unsigned NOT NULL default '0',
          `spoof_default_smtp` tinyint(1) unsigned NOT NULL default '0',
          `clickable_urls` tinyint(1) unsigned NOT NULL default '1',
          `allow_priority_change` tinyint(1) unsigned NOT NULL default '0',
          `use_email_priority` tinyint(1) unsigned NOT NULL default '0',
          `enable_captcha` tinyint(1) unsigned NOT NULL default '0',
          `enable_auto_cron` tinyint(1) unsigned NOT NULL default '0',
          `enable_mail_fetch` tinyint(1) unsigned NOT NULL default '0',
          `enable_email_piping` tinyint(1) unsigned NOT NULL default '0',
          `send_sql_errors` tinyint(1) unsigned NOT NULL default '1',
          `send_mailparse_errors` tinyint(1) unsigned NOT NULL default '1',
          `send_login_errors` tinyint(1) unsigned NOT NULL default '1',
          `save_email_headers` tinyint(1) unsigned NOT NULL default '1',
          `strip_quoted_reply` tinyint(1) unsigned NOT NULL default '1',
          `log_ticket_activity` tinyint(1) unsigned NOT NULL default '1',
          `ticket_autoresponder` tinyint(1) unsigned NOT NULL default '0',
          `message_autoresponder` tinyint(1) unsigned NOT NULL default '0',
          `ticket_notice_active` tinyint(1) unsigned NOT NULL default '0',
          `ticket_alert_active` tinyint(1) unsigned NOT NULL default '0',
          `ticket_alert_admin` tinyint(1) unsigned NOT NULL default '1',
          `ticket_alert_dept_manager` tinyint(1) unsigned NOT NULL default '1',
          `ticket_alert_dept_members` tinyint(1) unsigned NOT NULL default '0',
          `message_alert_active` tinyint(1) unsigned NOT NULL default '0',
          `message_alert_laststaff` tinyint(1) unsigned NOT NULL default '1',
          `message_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
          `message_alert_dept_manager` tinyint(1) unsigned NOT NULL default '0',
          `note_alert_active` tinyint(1) unsigned NOT NULL default '0',
          `note_alert_laststaff` tinyint(1) unsigned NOT NULL default '1',
          `note_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
          `note_alert_dept_manager` tinyint(1) unsigned NOT NULL default '0',
          `overdue_alert_active` tinyint(1) unsigned NOT NULL default '0',
          `overdue_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
          `overdue_alert_dept_manager` tinyint(1) unsigned NOT NULL default '1',
          `overdue_alert_dept_members` tinyint(1) unsigned NOT NULL default '0',
          `auto_assign_reopened_tickets` tinyint(1) unsigned NOT NULL default '1',
          `show_assigned_tickets` tinyint(1) unsigned NOT NULL default '0',
          `show_answered_tickets` tinyint(1) NOT NULL default '0',
          `hide_staff_name` tinyint(1) unsigned NOT NULL default '0',
          `overlimit_notice_active` tinyint(1) unsigned NOT NULL default '0',
          `email_attachments` tinyint(1) unsigned NOT NULL default '1',
          `allow_attachments` tinyint(1) unsigned NOT NULL default '0',
          `allow_email_attachments` tinyint(1) unsigned NOT NULL default '0',
          `allow_online_attachments` tinyint(1) unsigned NOT NULL default '0',
          `allow_online_attachments_onlogin` tinyint(1) unsigned NOT NULL default '0',
          `random_ticket_ids` tinyint(1) unsigned NOT NULL default '1',
          `log_level` tinyint(1) unsigned NOT NULL default '2',
          `log_graceperiod` int(10) unsigned NOT NULL default '12',
          `upload_dir` varchar(255) NOT NULL default '',
          `allowed_filetypes` varchar(255) NOT NULL default '.doc, .pdf, .zip, .jpg',
          `time_format` varchar(32) NOT NULL default ' h:i A',
          `date_format` varchar(32) NOT NULL default 'm/d/Y',
          `datetime_format` varchar(60) NOT NULL default 'm/d/Y g:i a',
          `daydatetime_format` varchar(60) NOT NULL default 'D, M j Y g:ia',
          `reply_separator` varchar(60) NOT NULL default '-- не изменять --',
          `admin_email` varchar(125) NOT NULL default '',
          `helpdesk_title` varchar(255) NOT NULL default 'Система технической поддержки TicketHub',
          `helpdesk_url` varchar(255) NOT NULL default '',
          `pipe_token` varchar(64) NOT NULL default '',
          `thversion` varchar(16) NOT NULL default '',
          `api_v2_enabled` tinyint(1) unsigned NOT NULL default '1',
          `api_v2_require_https` tinyint(1) unsigned NOT NULL default '0',
          `api_default_rate_limit` int(10) unsigned NOT NULL default '1000',
          `api_default_rate_window` int(10) unsigned NOT NULL default '3600',
          `api_log_retention_days` int(10) unsigned NOT NULL default '30',
          `api_max_per_page` int(10) unsigned NOT NULL default '100',
          `api_security_scan_enabled` int(10) NOT NULL default '1',
          `api_max_request_size` int(10) NOT NULL default '1048576',
          `api_brute_force_protection` int(10) NOT NULL default '1',
          `api_brute_force_max_attempts` int(10) NOT NULL default '5',
          `api_brute_force_window` int(10) NOT NULL default '300',
          `api_security_headers_enabled` int(10) NOT NULL default '1',
          `api_audit_log_enabled` int(10) NOT NULL default '1',
          `api_audit_log_retention_days` int(10) NOT NULL default '90',
          `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `isoffline` (`isonline`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}department` (
          `dept_id` int(11) unsigned NOT NULL auto_increment,
          `tpl_id` int(10) unsigned NOT NULL default '0',
          `email_id` int(10) unsigned NOT NULL default '0',
          `autoresp_email_id` int(10) unsigned NOT NULL default '0',
          `manager_id` int(10) unsigned NOT NULL default '0',
          `dept_name` varchar(32) NOT NULL default '',
          `dept_signature` tinytext NOT NULL,
          `ispublic` tinyint(1) unsigned NOT NULL default '1',
          `ticket_auto_response` tinyint(1) NOT NULL default '1',
          `message_auto_response` tinyint(1) NOT NULL default '0',
          `can_append_signature` tinyint(1) NOT NULL default '1',
          `updated` datetime default NULL,
          `created` datetime default NULL,
          PRIMARY KEY (`dept_id`),
          UNIQUE KEY `dept_name` (`dept_name`),
          KEY `manager_id` (`manager_id`),
          KEY `autoresp_email_id` (`autoresp_email_id`),
          KEY `tpl_id` (`tpl_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}email` (
          `email_id` int(11) unsigned NOT NULL auto_increment,
          `noautoresp` tinyint(1) unsigned NOT NULL default '0',
          `priority_id` tinyint(3) unsigned NOT NULL default '2',
          `dept_id` tinyint(3) unsigned NOT NULL default '0',
          `email` varchar(125) NOT NULL default '',
          `name` varchar(32) NOT NULL default '',
          `userid` varchar(125) NOT NULL,
          `userpass` varchar(125) NOT NULL,
          `mail_active` tinyint(1) NOT NULL default '0',
          `mail_host` varchar(125) NOT NULL,
          `mail_protocol` enum('POP','IMAP') NOT NULL default 'POP',
          `mail_encryption` enum('NONE','SSL') NOT NULL,
          `mail_port` int(6) default NULL,
          `mail_fetchfreq` tinyint(3) NOT NULL default '5',
          `mail_fetchmax` tinyint(4) NOT NULL default '30',
          `mail_delete` tinyint(1) NOT NULL default '0',
          `mail_errors` tinyint(3) NOT NULL default '0',
          `mail_lasterror` datetime default NULL,
          `mail_lastfetch` datetime default NULL,
          `smtp_active` tinyint(1) default '0',
          `smtp_host` varchar(125) NOT NULL,
          `smtp_port` int(6) default NULL,
          `smtp_secure` tinyint(1) NOT NULL default '1',
          `smtp_auth` tinyint(1) NOT NULL default '1',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`email_id`),
          UNIQUE KEY `email` (`email`),
          KEY `priority_id` (`priority_id`),
          KEY `dept_id` (`dept_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}email_banlist` (
          `id` int(11) NOT NULL auto_increment,
          `email` varchar(255) NOT NULL default '',
          `submitter` varchar(126) NOT NULL default '',
          `added` datetime default NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}email_template` (
          `tpl_id` int(11) NOT NULL auto_increment,
          `cfg_id` int(10) unsigned NOT NULL default '0',
          `name` varchar(32) NOT NULL default '',
          `notes` text,
          `ticket_autoresp_subj` varchar(255) NOT NULL default '',
          `ticket_autoresp_body` text NOT NULL,
          `ticket_notice_subj` varchar(255) NOT NULL,
          `ticket_notice_body` text NOT NULL,
          `ticket_alert_subj` varchar(255) NOT NULL default '',
          `ticket_alert_body` text NOT NULL,
          `message_autoresp_subj` varchar(255) NOT NULL default '',
          `message_autoresp_body` text NOT NULL,
          `message_alert_subj` varchar(255) NOT NULL default '',
          `message_alert_body` text NOT NULL,
          `note_alert_subj` varchar(255) NOT NULL,
          `note_alert_body` text NOT NULL,
          `assigned_alert_subj` varchar(255) NOT NULL default '',
          `assigned_alert_body` text NOT NULL,
          `ticket_overdue_subj` varchar(255) NOT NULL default '',
          `ticket_overdue_body` text NOT NULL,
          `ticket_overlimit_subj` varchar(255) NOT NULL default '',
          `ticket_overlimit_body` text NOT NULL,
          `ticket_reply_subj` varchar(255) NOT NULL default '',
          `ticket_reply_body` text NOT NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`tpl_id`),
          KEY `cfg_id` (`cfg_id`),
          FULLTEXT KEY `message_subj` (`ticket_reply_subj`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}groups` (
          `group_id` int(10) unsigned NOT NULL auto_increment,
          `group_enabled` tinyint(1) unsigned NOT NULL default '1',
          `group_name` varchar(50) NOT NULL default '',
          `dept_access` varchar(255) NOT NULL default '',
          `can_create_tickets` tinyint(1) unsigned NOT NULL default '1',
          `can_edit_tickets` tinyint(1) unsigned NOT NULL default '1',
          `can_delete_tickets` tinyint(1) unsigned NOT NULL default '0',
          `can_close_tickets` tinyint(1) unsigned NOT NULL default '0',
          `can_transfer_tickets` tinyint(1) unsigned NOT NULL default '1',
          `can_ban_emails` tinyint(1) unsigned NOT NULL default '0',
          `can_manage_kb` tinyint(1) unsigned NOT NULL default '0',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`group_id`),
          KEY `group_active` (`group_enabled`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}help_topic` (
          `topic_id` int(11) unsigned NOT NULL auto_increment,
          `isactive` tinyint(1) unsigned NOT NULL default '1',
          `noautoresp` tinyint(3) unsigned NOT NULL default '0',
          `priority_id` tinyint(3) unsigned NOT NULL default '0',
          `dept_id` tinyint(3) unsigned NOT NULL default '0',
          `topic` varchar(32) NOT NULL default '',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`topic_id`),
          UNIQUE KEY `topic` (`topic`),
          KEY `priority_id` (`priority_id`),
          KEY `dept_id` (`dept_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}kb_premade` (
          `premade_id` int(10) unsigned NOT NULL auto_increment,
          `dept_id` int(10) unsigned NOT NULL default '0',
          `isenabled` tinyint(1) unsigned NOT NULL default '1',
          `title` varchar(125) NOT NULL default '',
          `answer` text NOT NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`premade_id`),
          UNIQUE KEY `title_2` (`title`),
          KEY `dept_id` (`dept_id`),
          KEY `active` (`isenabled`),
          FULLTEXT KEY `title` (`title`,`answer`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}staff` (
          `staff_id` int(11) unsigned NOT NULL auto_increment,
          `group_id` int(10) unsigned NOT NULL default '0',
          `dept_id` int(10) unsigned NOT NULL default '0',
          `username` varchar(32) NOT NULL default '',
          `firstname` varchar(32) default NULL,
          `lastname` varchar(32) default NULL,
          `passwd` varchar(255) default NULL,
          `email` varchar(128) default NULL,
          `phone` varchar(24) NOT NULL default '',
          `phone_ext` varchar(6) default NULL,
          `mobile` varchar(24) NOT NULL default '',
          `signature` tinytext NOT NULL,
          `isactive` tinyint(1) NOT NULL default '1',
          `isadmin` tinyint(1) NOT NULL default '0',
          `isvisible` tinyint(1) unsigned NOT NULL default '1',
          `onvacation` tinyint(1) unsigned NOT NULL default '0',
          `daylight_saving` tinyint(1) unsigned NOT NULL default '0',
          `append_signature` tinyint(1) unsigned NOT NULL default '0',
          `change_passwd` tinyint(1) unsigned NOT NULL default '0',
          `timezone_offset` float(3,1) NOT NULL default '0.0',
          `max_page_size` int(11) unsigned NOT NULL default '0',
          `auto_refresh_rate` int(10) unsigned NOT NULL default '0',
          `created` datetime default NULL,
          `lastlogin` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`staff_id`),
          UNIQUE KEY `username` (`username`),
          KEY `dept_id` (`dept_id`),
          KEY `issuperuser` (`isadmin`),
          KEY `group_id` (`group_id`,`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}syslog` (
          `log_id` int(11) unsigned NOT NULL auto_increment,
          `log_type` enum('Debug','Warning','Error') NOT NULL,
          `title` varchar(255) NOT NULL,
          `log` text NOT NULL,
          `logger` varchar(64) NOT NULL,
          `ip_address` varchar(16) NOT NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`log_id`),
          KEY `log_type` (`log_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket` (
          `ticket_id` int(11) unsigned NOT NULL auto_increment,
          `ticketID` int(11) unsigned NOT NULL default '0',
          `dept_id` int(10) unsigned NOT NULL default '1',
          `priority_id` int(10) unsigned NOT NULL default '2',
          `topic_id` int(10) unsigned NOT NULL default '0',
          `staff_id` int(10) unsigned NOT NULL default '0',
          `andstaffs_id` varchar(255) default NULL,
          `email` varchar(120) NOT NULL default '',
          `name` varchar(32) NOT NULL default '',
          `subject` varchar(64) NOT NULL default '[no subject]',
          `helptopic` varchar(255) default NULL,
          `phone` varchar(16) default NULL,
          `phone_ext` varchar(8) default NULL,
          `ip_address` varchar(16) NOT NULL default '',
          `status` enum('open','closed') NOT NULL default 'open',
          `source` enum('Web','Email','Phone','Other') NOT NULL default 'Other',
          `isoverdue` tinyint(1) unsigned NOT NULL default '0',
          `isanswered` tinyint(1) unsigned NOT NULL default '0',
          `duedate` datetime default NULL,
          `reopened` datetime default NULL,
          `closed` datetime default NULL,
          `lastmessage` datetime default NULL,
          `lastresponse` datetime default NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`ticket_id`),
          UNIQUE KEY `email_extid` (`ticketID`,`email`),
          KEY `dept_id` (`dept_id`),
          KEY `staff_id` (`staff_id`),
          KEY `status` (`status`),
          KEY `priority_id` (`priority_id`),
          KEY `created` (`created`),
          KEY `closed` (`closed`),
          KEY `duedate` (`duedate`),
          KEY `topic_id` (`topic_id`),
          KEY `idx_status_dept` (`status`, `dept_id`, `created`),
          KEY `idx_staff_status` (`staff_id`, `status`, `created`),
          KEY `idx_status_overdue` (`status`, `isoverdue`, `created`),
          KEY `idx_dept_created` (`dept_id`, `created`),
          KEY `idx_created_status` (`created`, `status`),
          KEY `idx_isanswered` (`status`, `isanswered`, `created`),
          KEY `idx_priority_status` (`priority_id`, `status`, `created`),
          KEY `idx_ticketID` (`ticketID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_attachment` (
          `attach_id` int(11) unsigned NOT NULL auto_increment,
          `ticket_id` int(11) unsigned NOT NULL default '0',
          `ref_id` int(11) unsigned NOT NULL default '0',
          `ref_type` enum('M','R') NOT NULL default 'M',
          `file_size` varchar(32) NOT NULL default '',
          `file_name` varchar(128) NOT NULL default '',
          `file_key` varchar(128) NOT NULL default '',
          `deleted` tinyint(1) unsigned NOT NULL default '0',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`attach_id`),
          KEY `ticket_id` (`ticket_id`),
          KEY `ref_type` (`ref_type`),
          KEY `ref_id` (`ref_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_lock` (
          `lock_id` int(11) unsigned NOT NULL auto_increment,
          `ticket_id` int(11) unsigned NOT NULL default '0',
          `staff_id` int(10) unsigned NOT NULL default '0',
          `expire` datetime default NULL,
          `created` datetime default NULL,
          PRIMARY KEY (`lock_id`),
          UNIQUE KEY `ticket_id` (`ticket_id`),
          KEY `staff_id` (`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_message` (
          `msg_id` int(11) unsigned NOT NULL auto_increment,
          `ticket_id` int(11) unsigned NOT NULL default '0',
          `messageId` varchar(255) default NULL,
          `message` text NOT NULL,
          `headers` text,
          `source` varchar(16) default NULL,
          `ip_address` varchar(16) default NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`msg_id`),
          KEY `ticket_id` (`ticket_id`),
          KEY `msgId` (`messageId`),
          KEY `idx_ticket_id` (`ticket_id`, `created`),
          FULLTEXT KEY `message` (`message`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_note` (
          `note_id` int(11) unsigned NOT NULL auto_increment,
          `ticket_id` int(11) unsigned NOT NULL default '0',
          `staff_id` int(10) unsigned NOT NULL default '0',
          `source` varchar(32) NOT NULL default '',
          `title` varchar(255) NOT NULL default 'Generic Internal Notes',
          `note` text NOT NULL,
          `created` datetime default NULL,
          PRIMARY KEY (`note_id`),
          KEY `ticket_id` (`ticket_id`),
          KEY `staff_id` (`staff_id`),
          FULLTEXT KEY `note` (`note`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_priority` (
          `priority_id` tinyint(4) NOT NULL auto_increment,
          `priority` varchar(60) NOT NULL default '',
          `priority_desc` varchar(30) NOT NULL default '',
          `priority_color` varchar(7) NOT NULL default '',
          `priority_urgency` tinyint(1) unsigned NOT NULL default '0',
          `ispublic` tinyint(1) NOT NULL default '1',
          PRIMARY KEY (`priority_id`),
          UNIQUE KEY `priority` (`priority`),
          KEY `priority_urgency` (`priority_urgency`),
          KEY `ispublic` (`ispublic`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_response` (
          `response_id` int(11) unsigned NOT NULL auto_increment,
          `msg_id` int(11) unsigned NOT NULL default '0',
          `ticket_id` int(11) unsigned NOT NULL default '0',
          `staff_id` int(11) unsigned NOT NULL default '0',
          `staff_name` varchar(32) NOT NULL default '',
          `response` text NOT NULL,
          `ip_address` varchar(16) NOT NULL default '',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`response_id`),
          KEY `ticket_id` (`ticket_id`),
          KEY `msg_id` (`msg_id`),
          KEY `staff_id` (`staff_id`),
          KEY `idx_ticket_msg` (`ticket_id`, `msg_id`, `created`),
          FULLTEXT KEY `response` (`response`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}timezone` (
          `id` int(11) unsigned NOT NULL auto_increment,
          `offset` float(3,1) NOT NULL default '0.0',
          `timezone` varchar(255) NOT NULL default '',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}migrations` (
          `id` int(11) NOT NULL auto_increment,
          `migration` varchar(255) NOT NULL,
          `executed_at` datetime NOT NULL,
          `checksum` VARCHAR(64) NOT NULL DEFAULT '',
          `batch` INT NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          UNIQUE KEY `migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}ticket_archived` (
          `ticket_id` int(11) unsigned NOT NULL auto_increment,
          `ticketID` int(11) unsigned NOT NULL default '0',
          `dept_id` int(10) unsigned NOT NULL default '1',
          `priority_id` int(10) unsigned NOT NULL default '2',
          `topic_id` int(10) unsigned NOT NULL default '0',
          `staff_id` int(10) unsigned NOT NULL default '0',
          `andstaffs_id` varchar(255) default NULL,
          `email` varchar(120) NOT NULL default '',
          `name` varchar(32) NOT NULL default '',
          `subject` varchar(64) NOT NULL default '[no subject]',
          `helptopic` varchar(255) default NULL,
          `phone` varchar(16) default NULL,
          `phone_ext` varchar(8) default NULL,
          `ip_address` varchar(16) NOT NULL default '',
          `status` enum('open','closed') NOT NULL default 'closed',
          `source` enum('Web','Email','Phone','Other') NOT NULL default 'Other',
          `isoverdue` tinyint(1) unsigned NOT NULL default '0',
          `isanswered` tinyint(1) unsigned NOT NULL default '0',
          `duedate` datetime default NULL,
          `reopened` datetime default NULL,
          `closed` datetime default NULL,
          `lastmessage` datetime default NULL,
          `lastresponse` datetime default NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`ticket_id`),
          KEY `dept_id` (`dept_id`),
          KEY `staff_id` (`staff_id`),
          KEY `status` (`status`),
          KEY `created` (`created`),
          KEY `closed` (`closed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}locations` (
          `location_id` int(11) unsigned NOT NULL auto_increment,
          `location_name` varchar(255) NOT NULL,
          `parent_id` int(11) default NULL,
          `location_type` enum('building','floor','room','storage','rack','other') default 'room',
          `description` text,
          `sort_order` int(11) default '0',
          `is_active` tinyint(1) default '1',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`location_id`),
          KEY `idx_parent` (`parent_id`),
          KEY `idx_type` (`location_type`),
          KEY `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}kb_documents` (
          `doc_id` int(11) NOT NULL auto_increment,
          `title` varchar(255) NOT NULL,
          `description` text,
          `doc_type` enum('file','link') NOT NULL default 'file',
          `file_name` varchar(255) default NULL,
          `file_key` varchar(255) default NULL,
          `file_size` int(11) default '0',
          `file_mime` varchar(100) default NULL,
          `external_url` varchar(500) default NULL,
          `audience` enum('staff','client','all') NOT NULL default 'all',
          `dept_id` int(11) NOT NULL default '0',
          `staff_id` int(11) NOT NULL,
          `isenabled` tinyint(1) NOT NULL default '1',
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`doc_id`),
          KEY `idx_dept` (`dept_id`),
          KEY `idx_audience` (`audience`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_created` (`created`),
          KEY `idx_enabled_created` (`isenabled`, `created`),
          KEY `idx_dept_audience_enabled` (`dept_id`, `audience`, `isenabled`),
          FULLTEXT KEY `idx_search` (`title`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_boards` (
          `board_id` int(11) NOT NULL auto_increment,
          `board_name` varchar(255) NOT NULL,
          `board_type` enum('department','project') NOT NULL default 'project',
          `dept_id` int(11) NOT NULL default '0',
          `description` text,
          `color` varchar(7) default '#3498db',
          `is_archived` tinyint(1) NOT NULL default '0',
          `created_by` int(11) NOT NULL,
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`board_id`),
          KEY `idx_dept` (`dept_id`),
          KEY `idx_type` (`board_type`),
          KEY `idx_creator` (`created_by`),
          KEY `idx_archived` (`is_archived`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_lists` (
          `list_id` int(11) NOT NULL auto_increment,
          `board_id` int(11) NOT NULL,
          `list_name` varchar(255) NOT NULL,
          `status` varchar(50) default NULL,
          `list_order` int(11) NOT NULL default '0',
          `is_archived` tinyint(1) NOT NULL default '0',
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`list_id`),
          KEY `idx_board` (`board_id`),
          KEY `idx_order` (`board_id`, `list_order`),
          KEY `idx_board_order_archived` (`board_id`, `list_order`, `is_archived`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}tasks` (
          `task_id` int(11) NOT NULL auto_increment,
          `board_id` int(11) NOT NULL,
          `list_id` int(11) default NULL,
          `parent_task_id` int(11) default NULL,
          `ticket_id` int(11) default NULL,
          `title` varchar(255) NOT NULL,
          `description` longtext,
          `task_type` enum('action','meeting','call','email','other') NOT NULL default 'action',
          `priority` enum('low','normal','high','urgent') NOT NULL default 'normal',
          `status` enum('open','in_progress','review','blocked','completed','cancelled') NOT NULL default 'open',
          `start_date` datetime default NULL,
          `end_date` datetime default NULL,
          `deadline` datetime default NULL,
          `time_estimate` int(11) default '0',
          `position` int(11) NOT NULL default '0',
          `created_by` int(11) NOT NULL,
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          `completed_date` datetime default NULL,
          `is_archived` tinyint(1) NOT NULL default '0',
          PRIMARY KEY (`task_id`),
          KEY `idx_board` (`board_id`),
          KEY `idx_list` (`list_id`),
          KEY `idx_parent` (`parent_task_id`),
          KEY `idx_ticket` (`ticket_id`),
          KEY `idx_status` (`status`),
          KEY `idx_priority` (`priority`),
          KEY `idx_deadline` (`deadline`),
          KEY `idx_created_by` (`created_by`),
          KEY `idx_position` (`list_id`, `position`),
          KEY `idx_archived` (`is_archived`),
          KEY `idx_board_status_archived` (`board_id`, `status`, `is_archived`),
          KEY `idx_deadline_status` (`deadline`, `status`),
          KEY `idx_completed_date` (`completed_date`, `is_archived`),
          KEY `idx_list_status_position` (`list_id`, `status`, `position`),
          FULLTEXT KEY `idx_search` (`title`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_assignees` (
          `assignment_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `staff_id` int(11) NOT NULL,
          `role` enum('assignee','watcher','co-author') NOT NULL default 'assignee',
          `assigned_date` datetime NOT NULL,
          PRIMARY KEY (`assignment_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_role` (`role`),
          UNIQUE KEY `unique_assignment` (`task_id`, `staff_id`, `role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_tags` (
          `tag_id` int(11) NOT NULL auto_increment,
          `tag_name` varchar(100) NOT NULL,
          `tag_color` varchar(7) NOT NULL default '#3498db',
          `board_id` int(11) NOT NULL,
          `created` datetime NOT NULL,
          PRIMARY KEY (`tag_id`),
          KEY `idx_board` (`board_id`),
          UNIQUE KEY `unique_tag` (`board_id`, `tag_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_tag_associations` (
          `association_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `tag_id` int(11) NOT NULL,
          PRIMARY KEY (`association_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_tag` (`tag_id`),
          UNIQUE KEY `unique_assoc` (`task_id`, `tag_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_custom_fields` (
          `field_id` int(11) NOT NULL auto_increment,
          `board_id` int(11) NOT NULL,
          `field_name` varchar(255) NOT NULL,
          `field_type` enum('text','number','date','dropdown','checkbox','user','textarea') NOT NULL,
          `field_options` text,
          `is_required` tinyint(1) NOT NULL default '0',
          `field_order` int(11) NOT NULL default '0',
          `created` datetime NOT NULL,
          PRIMARY KEY (`field_id`),
          KEY `idx_board` (`board_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_custom_values` (
          `value_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `field_id` int(11) NOT NULL,
          `field_value` text,
          PRIMARY KEY (`value_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_field` (`field_id`),
          UNIQUE KEY `unique_value` (`task_id`, `field_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_attachments` (
          `attachment_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `file_name` varchar(255) NOT NULL,
          `file_key` varchar(255) NOT NULL,
          `file_size` int(11) NOT NULL default '0',
          `file_mime` varchar(100) NOT NULL,
          `uploaded_by` int(11) NOT NULL,
          `uploaded_date` datetime NOT NULL,
          PRIMARY KEY (`attachment_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_uploader` (`uploaded_by`),
          KEY `idx_task_uploaded` (`task_id`, `uploaded_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_comments` (
          `comment_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `staff_id` int(11) NOT NULL,
          `comment_text` text NOT NULL,
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`comment_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_created` (`created`),
          KEY `idx_task_created` (`task_id`, `created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_time_logs` (
          `log_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `staff_id` int(11) NOT NULL,
          `time_spent` int(11) NOT NULL default '0',
          `log_date` datetime NOT NULL,
          `notes` text,
          PRIMARY KEY (`log_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_date` (`log_date`),
          KEY `idx_task_date` (`task_id`, `log_date`),
          KEY `idx_staff_date` (`staff_id`, `log_date`, `time_spent`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_activity_log` (
          `activity_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `staff_id` int(11) NOT NULL,
          `activity_type` enum('created','updated','assigned','unassigned','commented','status_changed','moved','deleted','completed') NOT NULL,
          `activity_data` text,
          `created` datetime NOT NULL,
          PRIMARY KEY (`activity_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_type` (`activity_type`),
          KEY `idx_created` (`created`),
          KEY `idx_task_created` (`task_id`, `created`),
          KEY `idx_type_created` (`activity_type`, `created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_automation_rules` (
          `rule_id` int(11) NOT NULL auto_increment,
          `board_id` int(11) NOT NULL,
          `rule_name` varchar(255) NOT NULL,
          `trigger_type` enum('status_change','assignment','deadline_approaching','field_change','date_reached') NOT NULL,
          `trigger_config` text,
          `action_type` enum('change_status','assign_user','send_notification','add_tag','change_field') NOT NULL,
          `action_config` text,
          `is_enabled` tinyint(1) NOT NULL default '1',
          `created` datetime NOT NULL,
          PRIMARY KEY (`rule_id`),
          KEY `idx_board` (`board_id`),
          KEY `idx_enabled` (`is_enabled`),
          KEY `idx_board_enabled` (`board_id`, `is_enabled`, `trigger_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_recurring` (
          `recurring_id` int(11) NOT NULL auto_increment,
          `task_id` int(11) NOT NULL,
          `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
          `interval_value` int(11) NOT NULL default '1',
          `day_of_week` varchar(20) default NULL,
          `day_of_month` int(11) default NULL,
          `month_of_year` int(11) default NULL,
          `next_occurrence` datetime NOT NULL,
          `last_created` datetime default NULL,
          `is_active` tinyint(1) NOT NULL default '1',
          `end_date` datetime default NULL,
          PRIMARY KEY (`recurring_id`),
          KEY `idx_task` (`task_id`),
          KEY `idx_next` (`next_occurrence`),
          KEY `idx_active` (`is_active`),
          KEY `idx_active_next` (`is_active`, `next_occurrence`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_templates` (
          `template_id` int(11) NOT NULL auto_increment,
          `template_name` varchar(255) NOT NULL,
          `template_type` enum('task','project','board') NOT NULL,
          `template_data` longtext NOT NULL,
          `board_id` int(11) default NULL,
          `created_by` int(11) NOT NULL,
          `created` datetime NOT NULL,
          PRIMARY KEY (`template_id`),
          KEY `idx_type` (`template_type`),
          KEY `idx_board` (`board_id`),
          KEY `idx_creator` (`created_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_saved_filters` (
          `filter_id` int(11) NOT NULL auto_increment,
          `staff_id` int(11) NOT NULL,
          `filter_name` varchar(255) NOT NULL,
          `filter_config` text NOT NULL,
          `is_default` tinyint(1) NOT NULL default '0',
          `created` datetime NOT NULL,
          PRIMARY KEY (`filter_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_staff_default` (`staff_id`, `is_default`),
          UNIQUE KEY `unique_filter` (`staff_id`, `filter_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}task_board_permissions` (
          `permission_id` int(11) NOT NULL auto_increment,
          `board_id` int(11) NOT NULL,
          `staff_id` int(11) default NULL,
          `dept_id` int(11) default NULL,
          `permission_level` enum('view','edit','admin') NOT NULL default 'view',
          PRIMARY KEY (`permission_id`),
          KEY `idx_board` (`board_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_dept` (`dept_id`),
          KEY `idx_board_staff` (`board_id`, `staff_id`, `permission_level`),
          KEY `idx_board_dept` (`board_id`, `dept_id`, `permission_level`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_tokens` (
          `token_id` int(11) NOT NULL auto_increment,
          `token` varchar(64) NOT NULL,
          `name` varchar(255) NOT NULL,
          `description` text,
          `staff_id` int(11) default NULL,
          `token_type` enum('permanent','temporary','readonly','webhook') default 'permanent',
          `permissions` text,
          `ip_whitelist` text,
          `ip_check_enabled` tinyint(1) default '0',
          `rate_limit` int(11) default '1000',
          `rate_window` int(11) default '3600',
          `is_active` tinyint(1) default '1',
          `expires_at` datetime default NULL,
          `last_used_at` datetime default NULL,
          `last_used_ip` varchar(45) default NULL,
          `last_used_endpoint` varchar(255) default NULL,
          `total_requests` int(11) default '0',
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          PRIMARY KEY (`token_id`),
          UNIQUE KEY `token` (`token`),
          KEY `idx_active` (`is_active`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_expires` (`expires_at`),
          KEY `idx_type` (`token_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_logs` (
          `log_id` int(11) NOT NULL auto_increment,
          `token_id` int(11) NOT NULL,
          `endpoint` varchar(255) NOT NULL,
          `method` enum('GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD') NOT NULL,
          `query_params` text,
          `request_body` text,
          `response_code` int(11) NOT NULL,
          `response_time` int(11) default NULL,
          `response_size` int(11) default NULL,
          `error_message` text,
          `ip_address` varchar(45) NOT NULL,
          `user_agent` text,
          `request_id` varchar(64) default NULL,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`log_id`),
          KEY `idx_token` (`token_id`),
          KEY `idx_endpoint` (`endpoint`),
          KEY `idx_created` (`created_at`),
          KEY `idx_response_code` (`response_code`),
          KEY `idx_ip` (`ip_address`),
          KEY `idx_request_id` (`request_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_rate_limits` (
          `id` int(11) NOT NULL auto_increment,
          `token_id` int(11) NOT NULL,
          `window_start` datetime NOT NULL,
          `window_end` datetime NOT NULL,
          `requests_count` int(11) default '0',
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_token_window` (`token_id`, `window_start`),
          KEY `idx_window_end` (`window_end`),
          KEY `idx_token` (`token_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_webhooks` (
          `webhook_id` int(11) NOT NULL auto_increment,
          `token_id` int(11) NOT NULL,
          `name` varchar(255) NOT NULL,
          `url` varchar(500) NOT NULL,
          `secret` varchar(64) default NULL,
          `events` text NOT NULL,
          `is_active` tinyint(1) default '1',
          `last_triggered_at` datetime default NULL,
          `last_success_at` datetime default NULL,
          `last_failure_at` datetime default NULL,
          `total_triggers` int(11) default '0',
          `failed_triggers` int(11) default '0',
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          PRIMARY KEY (`webhook_id`),
          KEY `idx_token` (`token_id`),
          KEY `idx_active` (`is_active`),
          KEY `idx_url` (`url`(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_audit_log` (
          `log_id` int(10) unsigned NOT NULL auto_increment,
          `event_type` varchar(50) NOT NULL,
          `severity` enum('info','warning','error','critical') default 'info',
          `details` text,
          `ip_address` varchar(45) NOT NULL,
          `user_agent` varchar(255) default NULL,
          `token_id` int(10) unsigned default NULL,
          `created` datetime NOT NULL,
          PRIMARY KEY (`log_id`),
          KEY `idx_event_type` (`event_type`),
          KEY `idx_severity` (`severity`),
          KEY `idx_ip` (`ip_address`),
          KEY `idx_token` (`token_id`),
          KEY `idx_created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}api_ip_blacklist` (
          `blacklist_id` int(10) unsigned NOT NULL auto_increment,
          `ip_address` varchar(45) NOT NULL,
          `reason` varchar(255) NOT NULL,
          `expires_at` datetime default NULL,
          `created` datetime NOT NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`blacklist_id`),
          UNIQUE KEY `ip_address` (`ip_address`),
          KEY `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}inventory_categories` (
          `category_id` int(11) NOT NULL auto_increment,
          `parent_id` int(11) default NULL,
          `category_name` varchar(255) NOT NULL,
          `description` text,
          `icon` varchar(50) default 'desktop',
          `sort_order` int(11) default '0',
          `is_active` tinyint(1) default '1',
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`category_id`),
          KEY `idx_parent` (`parent_id`),
          KEY `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}inventory_brands` (
          `brand_id` int(11) NOT NULL auto_increment,
          `brand_name` varchar(255) NOT NULL,
          `is_active` tinyint(1) default '1',
          `created` datetime default NULL,
          PRIMARY KEY (`brand_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}inventory_models` (
          `model_id` int(11) NOT NULL auto_increment,
          `brand_id` int(11) NOT NULL,
          `category_id` int(11) default NULL,
          `model_name` varchar(255) NOT NULL,
          `is_active` tinyint(1) default '1',
          `created` datetime default NULL,
          PRIMARY KEY (`model_id`),
          KEY `idx_brand` (`brand_id`),
          KEY `idx_category` (`category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}inventory_items` (
          `item_id` int(11) NOT NULL auto_increment,
          `inventory_number` varchar(100) default NULL,
          `category_id` int(11) default NULL,
          `brand_id` int(11) default NULL,
          `model_id` int(11) default NULL,
          `custom_model` varchar(255) default NULL,
          `serial_number` varchar(255) default NULL,
          `part_number` varchar(255) default NULL,
          `location_id` int(11) default NULL,
          `assigned_staff_id` int(11) default NULL,
          `assignment_type` enum('workplace','remote','storage','repair','decommissioned') default 'workplace',
          `status` enum('active','in_repair','reserved','decommissioned','written_off') default 'active',
          `purchase_date` date default NULL,
          `warranty_until` date default NULL,
          `cost` decimal(12,2) default NULL,
          `description` text,
          `created_by` int(11) NOT NULL,
          `created` datetime default NULL,
          `updated` datetime default NULL,
          PRIMARY KEY (`item_id`),
          UNIQUE KEY `idx_inv_number` (`inventory_number`),
          KEY `idx_serial` (`serial_number`),
          KEY `idx_part` (`part_number`),
          KEY `idx_category` (`category_id`),
          KEY `idx_brand` (`brand_id`),
          KEY `idx_model` (`model_id`),
          KEY `idx_location` (`location_id`),
          KEY `idx_staff` (`assigned_staff_id`),
          KEY `idx_status` (`status`),
          KEY `idx_assignment` (`assignment_type`),
          FULLTEXT KEY `ft_search` (`inventory_number`, `serial_number`, `part_number`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}inventory_history` (
          `history_id` int(11) NOT NULL auto_increment,
          `item_id` int(11) NOT NULL,
          `action` enum('created','moved','assigned','status_changed','edited','decommissioned') NOT NULL,
          `old_value` text,
          `new_value` text,
          `staff_id` int(11) NOT NULL,
          `created` datetime default NULL,
          PRIMARY KEY (`history_id`),
          KEY `idx_item` (`item_id`),
          KEY `idx_staff` (`staff_id`),
          KEY `idx_action` (`action`),
          KEY `idx_created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        if (!db_query("CREATE TABLE IF NOT EXISTS `{$prefix}priority_users` (
          `id` int(11) NOT NULL auto_increment,
          `email` varchar(255) NOT NULL,
          `description` varchar(500) DEFAULT '',
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created` datetime NOT NULL,
          `updated` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_email` (`email`),
          KEY `idx_active` (`is_active`),
          KEY `idx_email_active` (`email`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
            return false;
        }

        db_query("INSERT IGNORE INTO `{$prefix}config` (id, updated, thversion) VALUES (1, NOW(), '')");

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}department`
             (dept_id, tpl_id, email_id, autoresp_email_id, manager_id, dept_name, dept_signature, ispublic, ticket_auto_response, message_auto_response, can_append_signature, updated, created)
             VALUES
             (1, 0, 1, 0, 0, %s, %s, 1, 1, 1, 1, NOW(), NOW()),
             (2, 0, 1, 0, 0, %s, %s, 1, 1, 1, 1, NOW(), NOW())",
            db_input('Технический отдел'),
            db_input('С уважением, технический отдел'),
            db_input('Отдел продаж'),
            db_input('С уважением, отдел продаж')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}email_banlist` (id, email, submitter, added) VALUES (1, %s, %s, NOW())",
            db_input('test@example.com'),
            db_input('Система')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}groups`
             (group_id, group_enabled, group_name, dept_access, can_create_tickets, can_edit_tickets, can_delete_tickets, can_close_tickets, can_transfer_tickets, can_ban_emails, can_manage_kb, created, updated)
             VALUES
             (1, 1, %s, '1', 1, 1, 1, 1, 1, 1, 1, NOW(), NOW()),
             (2, 1, %s, '1', 1, 1, 0, 1, 1, 1, 1, NOW(), NOW()),
             (3, 1, %s, '1', 1, 0, 0, 0, 0, 0, 0, NOW(), NOW())",
            db_input('Администраторы'),
            db_input('Менеджеры'),
            db_input('Персонал')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}help_topic`
             (topic_id, isactive, noautoresp, priority_id, dept_id, topic, created, updated)
             VALUES
             (1, 1, 0, 2, 1, %s, NOW(), NOW()),
             (2, 1, 0, 3, 1, %s, NOW(), NOW())",
            db_input('Техническая проблема'),
            db_input('Проблемы с оплатой')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}kb_premade`
             (premade_id, dept_id, isenabled, title, answer, created, updated)
             VALUES
             (1, 0, 1, %s, %s, NOW(), NOW()),
             (2, 0, 1, %s, %s, NOW(), NOW())",
            db_input('Что такое TicketHub?'),
            db_input("TicketHub — это система технической поддержки с открытым исходным кодом."),
            db_input('Шаблон ответа с переменными (пример)'),
            db_input("Здравствуйте, %name!\n\nВаша заявка #%ticket принята. С уважением, служба поддержки")
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}ticket_priority`
             (priority_id, priority, priority_desc, priority_color, priority_urgency, ispublic)
             VALUES
             (1, %s, %s, %s, 4, 1),
             (2, %s, %s, %s, 3, 1),
             (3, %s, %s, %s, 2, 1),
             (4, %s, %s, %s, 1, 0)",
            db_input('low'),    db_input('Низкий'),    db_input('#DDFFDD'),
            db_input('normal'), db_input('Средний'),   db_input('#FFFFF0'),
            db_input('high'),   db_input('Высокий'),   db_input('#FEE7E7'),
            db_input('emergency'), db_input('Критичный'), db_input('#FEE7E7')
        ));

        $timezones = [
            [1, -12.0, 'Eniwetok, Kwajalein'],
            [2, -11.0, 'Midway Island, Самоа'],
            [3, -10.0, 'Гаваи'],
            [4, -9.0,  'Аляска'],
            [5, -8.0,  'Pacific Time (США и Канада)'],
            [6, -7.0,  'Mountain Time (США и Канада)'],
            [7, -6.0,  'Central Time (США и Канада), Mexico City'],
            [8, -5.0,  'Eastern Time (США и Канада), Богота, Lima'],
            [9, -4.0,  'Atlantic Time (Канада), Каракас, La Paz'],
            [10, -3.5, 'Ньюфаундленд'],
            [11, -3.0, 'Бразилия, Буэнос айрес'],
            [12, -2.0, 'Mid-Atlantic'],
            [13, -1.0, 'Azores, Cape Verde Islands'],
            [14, 0.0,  'Западное Европейское Время, Лондон, Лисабон'],
            [15, 1.0,  'Брюссель, Копенгаген, Мадрид, Париж'],
            [16, 2.0,  'Калининград, Южная Африка'],
            [17, 3.0,  'Багдад, Москва, Санкт-Петербург'],
            [18, 3.5,  'Тегран'],
            [19, 4.0,  'Баку, Тбилиси, Ереван'],
            [20, 4.5,  'Кабул'],
            [21, 5.0,  'Катерингбург, Исламабад, Карачи'],
            [22, 5.5,  'Бомбей, Калькута'],
            [23, 6.0,  'Омск, Новосибирск, Алма-ата'],
            [24, 7.0,  'Красноярск, Бангкок, Ханой'],
            [25, 8.0,  'Иркутск, Сингапур, Гонк Конг'],
            [26, 9.0,  'Токио, Сеул, Осака, Якутск'],
            [27, 9.5,  'Аделаида, Дарвин'],
            [28, 10.0, 'Eastern Australia, Гуам, Владивосток'],
            [29, 11.0, 'Магадан, Соломоновы Острова'],
            [30, 12.0, 'Веллингтон, Фиджи, Камчатка'],
        ];
        foreach ($timezones as $tz) {
            db_query(sprintf(
                "INSERT IGNORE INTO `{$prefix}timezone` (id, offset, timezone) VALUES (%d, %s, %s)",
                (int)$tz[0],
                db_input((float)$tz[1]),
                db_input($tz[2])
            ));
        }

        $categories = [
            [1, null, 'Компьютеры',           'desktop',    1],
            [2, null, 'Мониторы',              'television', 2],
            [3, null, 'Принтеры/МФУ',          'print',      3],
            [4, null, 'Сетевое оборудование',  'sitemap',    4],
            [5, null, 'Периферия',             'keyboard-o', 5],
            [6, null, 'Серверное оборудование','server',     6],
            [7, null, 'Прочее',                'cube',       7],
            [8, 1,    'Ноутбуки',              'laptop',     1],
            [9, 1,    'Десктопы',              'desktop',    2],
            [10, 1,   'Моноблоки',             'tv',         3],
        ];
        foreach ($categories as $cat) {
            $parentSql = ($cat[1] === null) ? 'NULL' : (int)$cat[1];
            db_query(sprintf(
                "INSERT IGNORE INTO `{$prefix}inventory_categories`
                 (category_id, parent_id, category_name, icon, sort_order, created, updated)
                 VALUES (%d, {$parentSql}, %s, %s, %d, NOW(), NOW())",
                (int)$cat[0],
                db_input($cat[2]),
                db_input($cat[3]),
                (int)$cat[4]
            ));
        }

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}email_template`
             (tpl_id, cfg_id, name, notes,
              ticket_autoresp_subj, ticket_autoresp_body,
              ticket_notice_subj, ticket_notice_body,
              ticket_alert_subj, ticket_alert_body,
              message_autoresp_subj, message_autoresp_body,
              message_alert_subj, message_alert_body,
              note_alert_subj, note_alert_body,
              assigned_alert_subj, assigned_alert_body,
              ticket_overdue_subj, ticket_overdue_body,
              ticket_overlimit_subj, ticket_overlimit_body,
              ticket_reply_subj, ticket_reply_body,
              created, updated)
             VALUES
             (1, 1, %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              %s, %s,
              NOW(), NOW())",
            db_input('TicketHub Default Template'),
            db_input('Default TicketHub templates'),
            db_input('Support Ticket Opened [#%ticket]'),
            db_input('%name, запрос зарегистрирован под номером #%ticket. Специалист свяжется с вами в ближайшее время.'),
            db_input('[#%ticket] %subject'),
            db_input('%name, создана заявка #%ticket с сообщением: %message'),
            db_input('New Ticket Alert'),
            db_input('%staff, новая заявка #%ticket от %name (%email): %message'),
            db_input('[#%ticket] Message Added'),
            db_input('%name, ваш ответ на заявку #%ticket принят.'),
            db_input('New Message Alert'),
            db_input('%staff, новое сообщение к заявке #%ticket от %name: %message'),
            db_input('New Internal Note Alert'),
            db_input('%staff, внутренняя заметка добавлена к заявке #%ticket: %note'),
            db_input('Ticket #%ticket Assigned to you'),
            db_input('%assignee, заявка #%ticket назначена вам пользователем %assigner.'),
            db_input('Stale Ticket Alert'),
            db_input('%staff, заявка #%ticket просрочена. Пожалуйста, займитесь ею.'),
            db_input('Support Ticket Denied'),
            db_input('%name, превышен лимит открытых заявок. Закройте одну из существующих.'),
            db_input('[#%ticket] %subject'),
            db_input('%name, ответ сотрудника на заявку #%ticket: %response')
        ));

        return true;
    }

    public function down(): bool
    {
        return false;
    }
}
