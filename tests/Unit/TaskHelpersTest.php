<?php

use PHPUnit\Framework\TestCase;

class TaskHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskActivity')) {
            eval('class TaskActivity { static function log($a,$b,$c,$d=""){} static function deleteByTaskId($id){} }');
        }
        if (!class_exists('TaskAttachment')) {
            eval('class TaskAttachment { static function deleteByTaskId($id){} }');
        }
        if (!class_exists('TaskAutomation')) {
            eval('class TaskAutomation { static function fireEvent($a,$b,$c){} }');
        }
        if (!class_exists('Task')) {
            require_once INCLUDE_DIR . 'class.task.php';
        }
    }

    public function testGetPriorityLabels(): void
    {
        $labels = Task::getPriorityLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('low', $labels);
        $this->assertArrayHasKey('normal', $labels);
        $this->assertArrayHasKey('high', $labels);
        $this->assertArrayHasKey('urgent', $labels);
        $this->assertEquals('Низкий', $labels['low']);
        $this->assertEquals('Обычный', $labels['normal']);
        $this->assertEquals('Высокий', $labels['high']);
        $this->assertEquals('Срочный', $labels['urgent']);
    }

    public function testGetStatusLabels(): void
    {
        $labels = Task::getStatusLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('open', $labels);
        $this->assertArrayHasKey('in_progress', $labels);
        $this->assertArrayHasKey('blocked', $labels);
        $this->assertArrayHasKey('completed', $labels);
        $this->assertArrayHasKey('cancelled', $labels);
        $this->assertEquals('Открыта', $labels['open']);
        $this->assertEquals('В работе', $labels['in_progress']);
        $this->assertEquals('Заблокирована', $labels['blocked']);
        $this->assertEquals('Завершена', $labels['completed']);
        $this->assertEquals('Отменена', $labels['cancelled']);
    }

    public function testGetTaskTypeLabels(): void
    {
        $labels = Task::getTaskTypeLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('action', $labels);
        $this->assertArrayHasKey('meeting', $labels);
        $this->assertArrayHasKey('call', $labels);
        $this->assertArrayHasKey('email', $labels);
        $this->assertArrayHasKey('other', $labels);
    }

    public function testTaskInstanceGetters(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            [
                'task_id' => 1,
                'title' => 'Test Task',
                'description' => 'Description',
                'board_id' => 10,
                'list_id' => 20,
                'parent_task_id' => 0,
                'ticket_id' => null,
                'task_type' => 'action',
                'priority' => 'high',
                'status' => 'open',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-15',
                'deadline' => '2026-12-31',
                'time_estimate' => 120,
                'position' => 1,
                'created_by' => 5,
                'created' => '2026-01-01 10:00:00',
                'updated' => '2026-01-02 10:00:00',
                'completed_date' => null,
                'is_archived' => 0,
                'board_name' => 'Dev Board',
                'list_name' => 'Todo',
                'creator_name' => 'John Doe',
            ]
        ]);

        $task = new Task(1);

        $this->assertEquals(1, $task->getId());
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('Description', $task->getDescription());
        $this->assertEquals(10, $task->getBoardId());
        $this->assertEquals(20, $task->getListId());
        $this->assertEquals('action', $task->getTaskType());
        $this->assertEquals('high', $task->getPriority());
        $this->assertEquals('open', $task->getStatus());
        $this->assertEquals('Dev Board', $task->getBoardName());
        $this->assertEquals('Todo', $task->getListName());
        $this->assertEquals('John Doe', $task->getCreatorName());
    }

    public function testTaskStatusChecks(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            [
                'task_id' => 1,
                'title' => 'Test',
                'description' => '',
                'board_id' => 1,
                'list_id' => 1,
                'parent_task_id' => 0,
                'ticket_id' => null,
                'task_type' => 'action',
                'priority' => 'normal',
                'status' => 'completed',
                'start_date' => null,
                'end_date' => null,
                'deadline' => null,
                'time_estimate' => 0,
                'position' => 0,
                'created_by' => 1,
                'created' => '2026-01-01',
                'updated' => '2026-01-01',
                'completed_date' => '2026-01-02',
                'is_archived' => 0,
                'board_name' => 'Board',
                'list_name' => 'Done',
                'creator_name' => 'User',
            ]
        ]);

        $task = new Task(1);

        $this->assertFalse($task->isOpen());
        $this->assertFalse($task->isInProgress());
        $this->assertFalse($task->isBlocked());
        $this->assertTrue($task->isCompleted());
        $this->assertFalse($task->isCancelled());
        $this->assertFalse($task->isSubtask());
        $this->assertFalse($task->isArchived());
    }

    public function testTaskPriorityLabel(): void
    {
        DatabaseMock::reset();
        $this->setupTaskWithPriority('urgent');
        $task = new Task(1);

        $this->assertEquals('Срочный', $task->getPriorityLabel());
    }

    public function testTaskStatusLabel(): void
    {
        DatabaseMock::reset();
        $this->setupTaskWithStatus('in_progress');
        $task = new Task(1);

        $this->assertEquals('В работе', $task->getStatusLabel());
    }

    public function testTaskTypeLabel(): void
    {
        DatabaseMock::reset();
        $this->setupTaskWithType('meeting');
        $task = new Task(1);

        $this->assertEquals('Встреча', $task->getTaskTypeLabel());
    }

    public function testTaskPriorityClass(): void
    {
        DatabaseMock::reset();
        $this->setupTaskWithPriority('high');
        $task = new Task(1);

        $this->assertEquals('warning', $task->getPriorityClass());
    }

    public function testTaskStatusClass(): void
    {
        DatabaseMock::reset();
        $this->setupTaskWithStatus('blocked');
        $task = new Task(1);

        $this->assertEquals('danger', $task->getStatusClass());
    }

    public function testTaskIsOverdue(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow([
                'status' => 'open',
                'deadline' => '2020-01-01 00:00:00'
            ])
        ]);

        $task = new Task(1);
        $this->assertTrue($task->isOverdue());
    }

    public function testTaskIsNotOverdueWhenCompleted(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow([
                'status' => 'completed',
                'deadline' => '2020-01-01 00:00:00'
            ])
        ]);

        $task = new Task(1);
        $this->assertFalse($task->isOverdue());
    }

    public function testTaskTimeEstimateFormatted(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['time_estimate' => 150])
        ]);

        $task = new Task(1);
        $this->assertEquals('2ч 30м', $task->getTimeEstimateFormatted());
    }

    public function testTaskTimeEstimateFormattedHoursOnly(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['time_estimate' => 120])
        ]);

        $task = new Task(1);
        $this->assertEquals('2ч', $task->getTimeEstimateFormatted());
    }

    public function testTaskTimeEstimateFormattedMinutesOnly(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['time_estimate' => 45])
        ]);

        $task = new Task(1);
        $this->assertEquals('45м', $task->getTimeEstimateFormatted());
    }

    public function testTaskTimeEstimateFormattedZero(): void
    {
        DatabaseMock::reset();
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['time_estimate' => 0])
        ]);

        $task = new Task(1);
        $this->assertEquals('', $task->getTimeEstimateFormatted());
    }

    public function testTaskNotFoundReturnsZeroId(): void
    {
        DatabaseMock::reset();
        $task = new Task(999);
        $this->assertEquals(0, $task->getId());
    }

    public function testTaskLookupReturnsNullForInvalid(): void
    {
        DatabaseMock::reset();
        $result = Task::lookup(999);
        $this->assertNull($result);
    }

    public function testTaskCreateValidationNoTitle(): void
    {
        DatabaseMock::reset();
        $errors = [];
        $result = Task::create(['board_id' => 1, 'created_by' => 1], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testTaskCreateValidationNoBoard(): void
    {
        DatabaseMock::reset();
        $errors = [];
        $result = Task::create(['title' => 'Test', 'created_by' => 1], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testTaskCreateValidationNoCreator(): void
    {
        DatabaseMock::reset();
        $errors = [];
        $result = Task::create(['title' => 'Test', 'board_id' => 1], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('err', $errors);
    }

    private function setupTaskWithPriority(string $priority): void
    {
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['priority' => $priority])
        ]);
    }

    private function setupTaskWithStatus(string $status): void
    {
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['status' => $status])
        ]);
    }

    private function setupTaskWithType(string $type): void
    {
        DatabaseMock::setQueryResult('SELECT t.*', [
            $this->makeTaskRow(['task_type' => $type])
        ]);
    }

    private function makeTaskRow(array $overrides = []): array
    {
        return array_merge([
            'task_id' => 1,
            'title' => 'Test Task',
            'description' => '',
            'board_id' => 1,
            'list_id' => 1,
            'parent_task_id' => 0,
            'ticket_id' => null,
            'task_type' => 'action',
            'priority' => 'normal',
            'status' => 'open',
            'start_date' => null,
            'end_date' => null,
            'deadline' => null,
            'time_estimate' => 0,
            'position' => 0,
            'created_by' => 1,
            'created' => '2026-01-01',
            'updated' => '2026-01-01',
            'completed_date' => null,
            'is_archived' => 0,
            'board_name' => 'Board',
            'list_name' => 'List',
            'creator_name' => 'User',
        ], $overrides);
    }
}
