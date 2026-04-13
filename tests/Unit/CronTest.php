<?php

use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Cron')) {
            require_once INCLUDE_DIR . 'class.cron.php';
        }
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('Cron'));
    }

    public function testMethodMailFetcherExists(): void
    {
        $this->assertTrue(method_exists('Cron', 'MailFetcher'));
    }

    public function testMethodTicketMonitorExists(): void
    {
        $this->assertTrue(method_exists('Cron', 'TicketMonitor'));
    }

    public function testMethodPurgeLogsExists(): void
    {
        $this->assertTrue(method_exists('Cron', 'PurgeLogs'));
    }

    public function testMethodRunExists(): void
    {
        $this->assertTrue(method_exists('Cron', 'run'));
    }

    public function testAllMethodsAreStatic(): void
    {
        $reflection = new ReflectionClass('Cron');

        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);
        $staticNames = array_map(fn($m) => $m->getName(), $methods);

        $this->assertContains('MailFetcher', $staticNames);
        $this->assertContains('TicketMonitor', $staticNames);
        $this->assertContains('PurgeLogs', $staticNames);
        $this->assertContains('run', $staticNames);
    }
}
