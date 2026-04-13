<?php

use PHPUnit\Framework\TestCase;

class TaskTimeLogTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskTimeLog')) {
            require_once INCLUDE_DIR . 'class.tasktimelog.php';
        }

        DatabaseMock::reset();
    }

    private function makeTimeLogRow(array $overrides = []): array
    {
        return array_merge([
            'log_id' => 1,
            'task_id' => 1,
            'staff_id' => 1,
            'time_spent' => 60,
            'log_date' => '2026-01-15',
            'notes' => 'Worked on feature',
            'created' => '2026-01-15 10:00:00',
            'staff_name' => 'John Doe',
        ], $overrides);
    }

    private function setupTimeLogLookup(array $overrides = []): void
    {
        $row = $this->makeTimeLogRow($overrides);
        DatabaseMock::setQueryResult(TASK_TIME_LOGS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals(1, $log->getId());
    }

    public function testConstructorNotFound(): void
    {
        $log = new TaskTimeLog(999);
        $this->assertEquals(0, $log->getId());
    }

    public function testGetTaskId(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals(1, $log->getTaskId());
    }

    public function testGetStaffId(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals(1, $log->getStaffId());
    }

    public function testGetStaffName(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals('John Doe', $log->getStaffName());
    }

    public function testGetTimeSpent(): void
    {
        $this->setupTimeLogLookup(['time_spent' => 120]);
        $log = new TaskTimeLog(1);
        $this->assertEquals(120, $log->getTimeSpent());
    }

    public function testGetLogDate(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals('2026-01-15', $log->getLogDate());
    }

    public function testGetNotes(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $this->assertEquals('Worked on feature', $log->getNotes());
    }

    public function testGetInfo(): void
    {
        $this->setupTimeLogLookup();
        $log = new TaskTimeLog(1);
        $info = $log->getInfo();
        $this->assertIsArray($info);
    }

    public function testFormatMinutesHoursAndMinutes(): void
    {
        $this->assertEquals('1ч 30м', TaskTimeLog::formatMinutes(90));
    }

    public function testFormatMinutesHoursOnly(): void
    {
        $this->assertEquals('2ч', TaskTimeLog::formatMinutes(120));
    }

    public function testFormatMinutesMinutesOnly(): void
    {
        $this->assertEquals('45м', TaskTimeLog::formatMinutes(45));
    }

    public function testFormatMinutesZero(): void
    {
        $this->assertEquals('0м', TaskTimeLog::formatMinutes(0));
    }

    public function testFormatMinutesLargeValue(): void
    {
        $this->assertEquals('10ч', TaskTimeLog::formatMinutes(600));
    }

    public function testLookupFound(): void
    {
        $this->setupTimeLogLookup();
        $log = TaskTimeLog::lookup(1);
        $this->assertInstanceOf(TaskTimeLog::class, $log);
    }

    public function testLookupNotFound(): void
    {
        $log = TaskTimeLog::lookup(999);
        $this->assertNull($log);
    }

    public function testCreateValidationNoTaskId(): void
    {
        $errors = [];
        $result = TaskTimeLog::create([
            'task_id' => 0,
            'staff_id' => 1,
            'time_spent' => 60,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateValidationNoStaffId(): void
    {
        $errors = [];
        $result = TaskTimeLog::create([
            'task_id' => 1,
            'staff_id' => 0,
            'time_spent' => 60,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateValidationNoTimeSpent(): void
    {
        $errors = [];
        $result = TaskTimeLog::create([
            'task_id' => 1,
            'staff_id' => 1,
            'time_spent' => 0,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTimeLog::create([
            'task_id' => 1,
            'staff_id' => 1,
            'time_spent' => 60,
            'log_date' => '2026-01-15',
            'notes' => 'Test',
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testGetByTaskId(): void
    {
        DatabaseMock::setQueryResult(TASK_TIME_LOGS_TABLE, [
            $this->makeTimeLogRow(),
            $this->makeTimeLogRow(['log_id' => 2, 'time_spent' => 30]),
        ]);
        $logs = TaskTimeLog::getByTaskId(1);
        $this->assertIsArray($logs);
        $this->assertCount(2, $logs);
    }

    public function testGetTotalByTask(): void
    {
        DatabaseMock::setQueryResult('SUM(time_spent)', [['total' => 180]]);
        $total = TaskTimeLog::getTotalByTask(1);
        $this->assertEquals(180, $total);
    }

    public function testDeleteLog(): void
    {
        $result = TaskTimeLog::deleteLog(1);
        $this->assertTrue($result);
    }

    public function testDeleteByTaskId(): void
    {
        $result = TaskTimeLog::deleteByTaskId(1);
        $this->assertTrue($result);
    }
}
