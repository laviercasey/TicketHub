<?php

use PHPUnit\Framework\TestCase;

class TicketLockTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TicketLock')) {
            require_once INCLUDE_DIR . 'class.lock.php';
        }
        DatabaseMock::reset();
    }

    public function testLoadLockById(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertEquals(1, $lock->getId());
        $this->assertEquals(5, $lock->getStaffId());
    }

    public function testLoadLockNotFound(): void
    {
        $lock = new TicketLock(999);
        $this->assertEquals(0, $lock->getId());
    }

    public function testLoadLockNoId(): void
    {
        $lock = new TicketLock(0);
        $this->assertEquals(0, $lock->getId());
    }

    public function testGetCreateTime(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertEquals('2026-01-15 10:00:00', $lock->getCreateTime());
    }

    public function testGetExpireTime(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertEquals('2026-01-15 10:03:00', $lock->getExpireTime());
    }

    public function testIsExpiredWhenTimeleftPositive(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 3600,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertFalse($lock->isExpired());
    }

    public function testIsExpiredWhenTimeleftNegative(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => -100,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertTrue($lock->isExpired());
    }

    public function testGetTimeReturnsRemainingSeconds(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 3600,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertGreaterThan(0, $lock->getTime());
    }

    public function testGetTimeReturnsZeroWhenExpired(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => -100,
            ]
        ]);

        $lock = new TicketLock(1);
        $this->assertEquals(0, $lock->getTime());
    }

    public function testAcquireCreatesLock(): void
    {
        $cfg = new stdClass();
        $cfg->lockTime = 3;
        $GLOBALS['cfg'] = $cfg;

        if (!method_exists($cfg, 'getLockTime')) {
            $cfg = new class {
                public function getLockTime() { return 3; }
            };
            $GLOBALS['cfg'] = $cfg;
        }

        DatabaseMock::setLastInsertId(42);
        $lockId = TicketLock::acquire(10, 5);
        $this->assertEquals(42, $lockId);
    }

    public function testAcquireReturnsZeroNoTicketId(): void
    {
        $cfg = new class {
            public function getLockTime() { return 3; }
        };
        $GLOBALS['cfg'] = $cfg;

        $lockId = TicketLock::acquire(0, 5);
        $this->assertEquals(0, $lockId);
    }

    public function testAcquireReturnsZeroNoStaffId(): void
    {
        $cfg = new class {
            public function getLockTime() { return 3; }
        };
        $GLOBALS['cfg'] = $cfg;

        $lockId = TicketLock::acquire(10, 0);
        $this->assertEquals(0, $lockId);
    }

    public function testAcquireReturnsZeroNoLockTime(): void
    {
        $cfg = new class {
            public function getLockTime() { return 0; }
        };
        $GLOBALS['cfg'] = $cfg;

        $lockId = TicketLock::acquire(10, 5);
        $this->assertEquals(0, $lockId);
    }

    public function testReleaseDeletesLock(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);
        DatabaseMock::setAffectedRows(1);

        $lock = new TicketLock(1);
        $this->assertTrue($lock->release());
    }

    public function testReleaseReturnsFalseOnFailure(): void
    {
        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);
        DatabaseMock::setAffectedRows(0);

        $lock = new TicketLock(1);
        $this->assertFalse($lock->release());
    }

    public function testRenewLock(): void
    {
        $cfg = new class {
            public function getLockTime() { return 3; }
        };
        $GLOBALS['cfg'] = $cfg;

        DatabaseMock::setQueryResult('SELECT *,TIME_TO_SEC', [
            [
                'lock_id' => 1,
                'staff_id' => 5,
                'ticket_id' => 10,
                'created' => '2026-01-15 10:00:00',
                'expire' => '2026-01-15 10:03:00',
                'timeleft' => 120,
            ]
        ]);
        DatabaseMock::setAffectedRows(1);

        $lock = new TicketLock(1);
        $this->assertTrue($lock->renew());
    }

    public function testRemoveStaffLocks(): void
    {
        $result = TicketLock::removeStaffLocks(5);
        $this->assertTrue($result);
    }

    public function testRemoveStaffLocksForTicket(): void
    {
        $result = TicketLock::removeStaffLocks(5, 10);
        $this->assertTrue($result);

        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('staff_id', $lastQuery);
        $this->assertStringContainsString('ticket_id', $lastQuery);
    }

    public function testCleanupRunsDeleteQuery(): void
    {
        TicketLock::cleanup();
        $queries = DatabaseMock::getExecutedQueries();
        $found = false;
        foreach ($queries as $q) {
            if (stripos($q, 'DELETE FROM') !== false && stripos($q, 'expire<NOW()') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testConstructorWithLoadFalse(): void
    {
        $lock = new TicketLock(5, false);
        $this->assertEquals(5, $lock->getId());
    }
}
