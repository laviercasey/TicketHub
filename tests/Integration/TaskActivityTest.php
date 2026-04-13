<?php

use PHPUnit\Framework\TestCase;

class TaskActivityTest extends TestCase
{
    protected function setUp(): void
    {
        DatabaseMock::reset();
    }

    private function makeActivityRow(array $overrides = []): array
    {
        return array_merge([
            'activity_id' => 1,
            'task_id' => 1,
            'staff_id' => 1,
            'activity_type' => 'created',
            'activity_data' => 'Test Task',
            'created' => '2026-01-01 00:00:00',
            'staff_name' => 'John Doe',
        ], $overrides);
    }

    private function setupActivityLookup(array $overrides = []): void
    {
        $row = $this->makeActivityRow($overrides);
        DatabaseMock::setQueryResult(TASK_ACTIVITY_LOG_TABLE, [$row]);
    }

    public function testConstructorLoadsActivity(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $this->assertEquals(1, $activity->getId());
    }

    public function testConstructorNotFound(): void
    {
        $activity = new TaskActivity(999);
        $this->assertEquals(0, $activity->getId());
    }

    public function testGetTaskId(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $this->assertEquals(1, $activity->getTaskId());
    }

    public function testGetStaffId(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $this->assertEquals(1, $activity->getStaffId());
    }

    public function testGetType(): void
    {
        $this->setupActivityLookup(['activity_type' => 'updated']);
        $activity = new TaskActivity(1);
        $this->assertEquals('updated', $activity->getType());
    }

    public function testGetData(): void
    {
        $this->setupActivityLookup(['activity_data' => 'Some data']);
        $activity = new TaskActivity(1);
        $this->assertEquals('Some data', $activity->getData());
    }

    public function testGetCreated(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $this->assertEquals('2026-01-01 00:00:00', $activity->getCreated());
    }

    public function testGetStaffName(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $this->assertEquals('John Doe', $activity->getStaffName());
    }

    public function testGetInfo(): void
    {
        $this->setupActivityLookup();
        $activity = new TaskActivity(1);
        $info = $activity->getInfo();
        $this->assertIsArray($info);
    }

    public function testGetTypeLabelCreated(): void
    {
        $this->setupActivityLookup(['activity_type' => 'created']);
        $activity = new TaskActivity(1);
        $this->assertEquals('создал(а) задачу', $activity->getTypeLabel());
    }

    public function testGetTypeLabelUpdated(): void
    {
        $this->setupActivityLookup(['activity_type' => 'updated']);
        $activity = new TaskActivity(1);
        $this->assertEquals('обновил(а) задачу', $activity->getTypeLabel());
    }

    public function testGetTypeLabelStatusChanged(): void
    {
        $this->setupActivityLookup(['activity_type' => 'status_changed']);
        $activity = new TaskActivity(1);
        $this->assertEquals('изменил(а) статус', $activity->getTypeLabel());
    }

    public function testGetTypeLabelCompleted(): void
    {
        $this->setupActivityLookup(['activity_type' => 'completed']);
        $activity = new TaskActivity(1);
        $this->assertEquals('завершил(а) задачу', $activity->getTypeLabel());
    }

    public function testGetTypeLabelUnknown(): void
    {
        $this->setupActivityLookup(['activity_type' => 'unknown_type']);
        $activity = new TaskActivity(1);
        $this->assertEquals('unknown_type', $activity->getTypeLabel());
    }

    public function testGetTypeIconCreated(): void
    {
        $this->setupActivityLookup(['activity_type' => 'created']);
        $activity = new TaskActivity(1);
        $this->assertEquals('plus-circle text-success', $activity->getTypeIcon());
    }

    public function testGetTypeIconDeleted(): void
    {
        $this->setupActivityLookup(['activity_type' => 'deleted']);
        $activity = new TaskActivity(1);
        $this->assertEquals('trash text-danger', $activity->getTypeIcon());
    }

    public function testGetTypeIconUnknown(): void
    {
        $this->setupActivityLookup(['activity_type' => 'unknown']);
        $activity = new TaskActivity(1);
        $this->assertEquals('circle-o', $activity->getTypeIcon());
    }

    public function testLogValidType(): void
    {
        $result = TaskActivity::log(1, 1, 'created', 'Test');
        $this->assertTrue($result);
    }

    public function testLogInvalidType(): void
    {
        $result = TaskActivity::log(1, 1, 'invalid_type', 'Test');
        $this->assertFalse($result);
    }

    public function testLogAllValidTypes(): void
    {
        $types = ['created', 'updated', 'assigned', 'unassigned', 'commented',
                  'status_changed', 'moved', 'deleted', 'completed', 'automation', 'notification'];
        foreach ($types as $type) {
            $result = TaskActivity::log(1, 1, $type, '');
            $this->assertTrue($result, "Log should succeed for type: $type");
        }
    }

    public function testGetByTaskId(): void
    {
        DatabaseMock::setQueryResult(TASK_ACTIVITY_LOG_TABLE, [
            $this->makeActivityRow(),
            $this->makeActivityRow(['activity_id' => 2, 'activity_type' => 'updated']),
        ]);
        $activities = TaskActivity::getByTaskId(1);
        $this->assertIsArray($activities);
        $this->assertCount(2, $activities);
    }

    public function testDeleteByTaskId(): void
    {
        $result = TaskActivity::deleteByTaskId(1);
        $this->assertTrue($result);
    }
}
