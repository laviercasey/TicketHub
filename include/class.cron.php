<?php
class Cron {

    static function MailFetcher() {
        require_once(INCLUDE_DIR.'class.mailfetch.php');
        MailFetcher::fetchMail(); //Fetch mail..frequency is limited by email account setting.
    }

    static function TicketMonitor() {
        require_once(INCLUDE_DIR.'class.ticket.php');
        require_once(INCLUDE_DIR.'class.lock.php');
        Ticket::checkOverdue(); //Make stale tickets overdue
        TicketLock::cleanup(); //Remove expired locks 
    }

    static function PurgeLogs() {
        Sys::purgeLogs();
    }

    static function run(){ //called by outside cron NOT autocron
        Cron::MailFetcher();
        Cron::TicketMonitor();
        cron::PurgeLogs();
    }
}
?>
