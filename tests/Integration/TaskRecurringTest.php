<?php

use PHPUnit\Framework\TestCase;

class TaskRecurringTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskRecurring')) {
            require_once INCLUDE_DIR . 'class.taskrecurring.php';
        }

        DatabaseMock::reset();
    }

    private function makeRecurringRow(array $overrides = []): array
    {
        return array_merge([
            'recurring_id' => 1,
            'task_id' => 1,
            'frequency' => 'weekly',
            'interval_value' => 1,
            'day_of_week' => '1,3,5',
            'next_occurrence' => '2026-03-15 09:00:00',
            'is_active' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupRecurringLookup(array $overrides = []): void
    {
        $row = $this->makeRecurringRow($overrides);
        DatabaseMock::setQueryResult(TASK_RECURRING_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertEquals(1, $rec->getId());
    }

    public function testConstructorNotFound(): void
    {
        $rec = new TaskRecurring(999);
        $this->assertEquals(0, $rec->getId());
    }

    public function testGetTaskId(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertEquals(1, $rec->getTaskId());
    }

    public function testGetFrequency(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertEquals('weekly', $rec->getFrequency());
    }

    public function testGetIntervalValue(): void
    {
        $this->setupRecurringLookup(['interval_value' => 2]);
        $rec = new TaskRecurring(1);
        $this->assertEquals(2, $rec->getIntervalValue());
    }

    public function testGetDayOfWeek(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertEquals('1,3,5', $rec->getDayOfWeek());
    }

    public function testGetNextOccurrence(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertEquals('2026-03-15 09:00:00', $rec->getNextOccurrence());
    }

    public function testIsActive(): void
    {
        $this->setupRecurringLookup(['is_active' => 1]);
        $rec = new TaskRecurring(1);
        $this->assertTrue($rec->isActive());
    }

    public function testIsNotActive(): void
    {
        $this->setupRecurringLookup(['is_active' => 0]);
        $rec = new TaskRecurring(1);
        $this->assertFalse($rec->isActive());
    }

    public function testGetInfo(): void
    {
        $this->setupRecurringLookup();
        $rec = new TaskRecurring(1);
        $this->assertIsArray($rec->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupRecurringLookup();
        $rec = TaskRecurring::lookup(1);
        $this->assertInstanceOf(TaskRecurring::class, $rec);
    }

    public function testLookupNotFound(): void
    {
        $rec = TaskRecurring::lookup(999);
        $this->assertNull($rec);
    }

    public function testCreateValidationNoTaskId(): void
    {
        $errors = [];
        $result = TaskRecurring::create([
            'task_id' => 0,
            'frequency' => 'daily',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('task_id', $errors);
    }

    public function testCreateValidationInvalidFrequency(): void
    {
        $errors = [];
        $result = TaskRecurring::create([
            'task_id' => 1,
            'frequency' => 'invalid',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('frequency', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskRecurring::create([
            'task_id' => 1,
            'frequency' => 'daily',
            'interval_value' => 1,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateNoId(): void
    {
        $errors = [];
        $result = TaskRecurring::update(0, ['frequency' => 'daily'], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateInvalidFrequency(): void
    {
        $errors = [];
        $result = TaskRecurring::update(1, ['frequency' => 'invalid'], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskRecurring::update(1, [
            'frequency' => 'monthly',
            'interval_value' => 2,
        ], $errors);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $this->assertTrue(TaskRecurring::delete(1));
    }

    public function testDeleteByTaskId(): void
    {
        $this->assertTrue(TaskRecurring::deleteByTaskId(1));
    }

    public function testCalculateNextOccurrenceDaily(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('daily', 1, '', '2026-03-10 09:00:00');
        $this->assertEquals('2026-03-11 09:00:00', $result);
    }

    public function testCalculateNextOccurrenceDailyInterval3(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('daily', 3, '', '2026-03-10 09:00:00');
        $this->assertEquals('2026-03-13 09:00:00', $result);
    }

    public function testCalculateNextOccurrenceWeeklyNoDay(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('weekly', 1, '', '2026-03-10 09:00:00');
        $this->assertEquals('2026-03-17 09:00:00', $result);
    }

    public function testCalculateNextOccurrenceMonthly(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('monthly', 1, '', '2026-03-10 09:00:00');
        $this->assertEquals('2026-04-10 09:00:00', $result);
    }

    public function testCalculateNextOccurrenceYearly(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('yearly', 1, '', '2026-03-10 09:00:00');
        $this->assertEquals('2027-03-10 09:00:00', $result);
    }

    public function testCalculateNextOccurrenceUnknown(): void
    {
        $result = TaskRecurring::calculateNextOccurrence('unknown', 1, '', '2026-03-10 09:00:00');
        $this->assertEquals('2026-03-11 09:00:00', $result);
    }

    public function testGetFrequencyLabels(): void
    {
        $labels = TaskRecurring::getFrequencyLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('daily', $labels);
        $this->assertArrayHasKey('weekly', $labels);
        $this->assertArrayHasKey('monthly', $labels);
        $this->assertArrayHasKey('yearly', $labels);
        $this->assertCount(4, $labels);
    }

    public function testGetDayLabels(): void
    {
        $labels = TaskRecurring::getDayLabels();
        $this->assertIsArray($labels);
        $this->assertCount(7, $labels);
        $this->assertEquals('Пн', $labels[1]);
        $this->assertEquals('Вс', $labels[7]);
    }

    public function testGetFrequencyLabel(): void
    {
        $this->setupRecurringLookup(['frequency' => 'weekly']);
        $rec = new TaskRecurring(1);
        $this->assertEquals('Еженедельно', $rec->getFrequencyLabel());
    }

    public function testGetFrequencyLabelUnknown(): void
    {
        $this->setupRecurringLookup(['frequency' => 'custom']);
        $rec = new TaskRecurring(1);
        $this->assertEquals('custom', $rec->getFrequencyLabel());
    }
}
