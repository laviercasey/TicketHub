<?php

use PHPUnit\Framework\TestCase;

class TaskCrudTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Task')) {
            require_once INCLUDE_DIR . 'class.task.php';
        }

        DatabaseMock::reset();
    }

    private function makeTaskRow(array $overrides = []): array
    {
        return array_merge([
            'task_id' => 1,
            'title' => 'Test Task',
            'description' => 'Test Description',
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
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
            'completed_date' => null,
            'is_archived' => 0,
            'board_name' => 'Board',
            'list_name' => 'List',
            'creator_name' => 'User',
        ], $overrides);
    }

    private function makeCreateData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'New Task',
            'board_id' => 1,
            'list_id' => 0,
            'parent_task_id' => 0,
            'ticket_id' => null,
            'description' => '',
            'task_type' => 'action',
            'priority' => 'normal',
            'status' => 'open',
            'start_date' => null,
            'end_date' => null,
            'deadline' => null,
            'time_estimate' => 0,
            'created_by' => 1,
        ], $overrides);
    }

    private function setupTaskLookup(array $overrides = []): void
    {
        $row = $this->makeTaskRow($overrides);
        DatabaseMock::setQueryResult('SELECT t.*', [$row]);
    }

    public function testConstructorLoadsTask(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals(1, $task->getId());
    }

    public function testConstructorNotFound(): void
    {
        $task = new Task(999);
        $this->assertEquals(0, $task->getId());
    }

    public function testGetTitle(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('Test Task', $task->getTitle());
    }

    public function testGetDescription(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('Test Description', $task->getDescription());
    }

    public function testGetBoardId(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals(1, $task->getBoardId());
    }

    public function testGetListId(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals(1, $task->getListId());
    }

    public function testGetParentTaskId(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals(0, $task->getParentTaskId());
    }

    public function testGetTaskType(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('action', $task->getTaskType());
    }

    public function testGetPriority(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('normal', $task->getPriority());
    }

    public function testGetStatus(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('open', $task->getStatus());
    }

    public function testGetCreatedBy(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals(1, $task->getCreatedBy());
    }

    public function testGetCreated(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('2026-01-01 00:00:00', $task->getCreated());
    }

    public function testGetBoardName(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('Board', $task->getBoardName());
    }

    public function testGetListName(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('List', $task->getListName());
    }

    public function testGetCreatorName(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $this->assertEquals('User', $task->getCreatorName());
    }

    public function testGetInfo(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $info = $task->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('Test Task', $info['title']);
    }

    public function testIsOpen(): void
    {
        $this->setupTaskLookup(['status' => 'open']);
        $task = new Task(1);
        $this->assertTrue($task->isOpen());
    }

    public function testIsInProgress(): void
    {
        $this->setupTaskLookup(['status' => 'in_progress']);
        $task = new Task(1);
        $this->assertTrue($task->isInProgress());
    }

    public function testIsBlocked(): void
    {
        $this->setupTaskLookup(['status' => 'blocked']);
        $task = new Task(1);
        $this->assertTrue($task->isBlocked());
    }

    public function testIsCompleted(): void
    {
        $this->setupTaskLookup(['status' => 'completed']);
        $task = new Task(1);
        $this->assertTrue($task->isCompleted());
    }

    public function testIsCancelled(): void
    {
        $this->setupTaskLookup(['status' => 'cancelled']);
        $task = new Task(1);
        $this->assertTrue($task->isCancelled());
    }

    public function testIsSubtask(): void
    {
        $this->setupTaskLookup(['parent_task_id' => 5]);
        $task = new Task(1);
        $this->assertTrue($task->isSubtask());
    }

    public function testIsNotSubtask(): void
    {
        $this->setupTaskLookup(['parent_task_id' => 0]);
        $task = new Task(1);
        $this->assertFalse($task->isSubtask());
    }

    public function testIsArchived(): void
    {
        $this->setupTaskLookup(['is_archived' => 1]);
        $task = new Task(1);
        $this->assertTrue($task->isArchived());
    }

    public function testIsNotArchived(): void
    {
        $this->setupTaskLookup(['is_archived' => 0]);
        $task = new Task(1);
        $this->assertFalse($task->isArchived());
    }

    public function testGetPriorityLabelNormal(): void
    {
        $this->setupTaskLookup(['priority' => 'normal']);
        $task = new Task(1);
        $this->assertEquals('Обычный', $task->getPriorityLabel());
    }

    public function testGetPriorityLabelUrgent(): void
    {
        $this->setupTaskLookup(['priority' => 'urgent']);
        $task = new Task(1);
        $this->assertEquals('Срочный', $task->getPriorityLabel());
    }

    public function testGetStatusLabelOpen(): void
    {
        $this->setupTaskLookup(['status' => 'open']);
        $task = new Task(1);
        $this->assertEquals('Открыта', $task->getStatusLabel());
    }

    public function testGetStatusLabelCompleted(): void
    {
        $this->setupTaskLookup(['status' => 'completed']);
        $task = new Task(1);
        $this->assertEquals('Завершена', $task->getStatusLabel());
    }

    public function testGetTaskTypeLabelAction(): void
    {
        $this->setupTaskLookup(['task_type' => 'action']);
        $task = new Task(1);
        $this->assertEquals('Действие', $task->getTaskTypeLabel());
    }

    public function testGetTaskTypeLabelMeeting(): void
    {
        $this->setupTaskLookup(['task_type' => 'meeting']);
        $task = new Task(1);
        $this->assertEquals('Встреча', $task->getTaskTypeLabel());
    }

    public function testGetPriorityClassNormal(): void
    {
        $this->setupTaskLookup(['priority' => 'normal']);
        $task = new Task(1);
        $this->assertEquals('info', $task->getPriorityClass());
    }

    public function testGetPriorityClassUrgent(): void
    {
        $this->setupTaskLookup(['priority' => 'urgent']);
        $task = new Task(1);
        $this->assertEquals('danger', $task->getPriorityClass());
    }

    public function testGetStatusClassOpen(): void
    {
        $this->setupTaskLookup(['status' => 'open']);
        $task = new Task(1);
        $this->assertEquals('default', $task->getStatusClass());
    }

    public function testGetStatusClassCompleted(): void
    {
        $this->setupTaskLookup(['status' => 'completed']);
        $task = new Task(1);
        $this->assertEquals('success', $task->getStatusClass());
    }

    public function testIsOverdueWithPastDeadline(): void
    {
        $this->setupTaskLookup([
            'deadline' => '2020-01-01 00:00:00',
            'status' => 'open',
        ]);
        $task = new Task(1);
        $this->assertTrue($task->isOverdue());
    }

    public function testIsNotOverdueWhenCompleted(): void
    {
        $this->setupTaskLookup([
            'deadline' => '2020-01-01 00:00:00',
            'status' => 'completed',
        ]);
        $task = new Task(1);
        $this->assertFalse($task->isOverdue());
    }

    public function testIsNotOverdueWithNoDeadline(): void
    {
        $this->setupTaskLookup(['deadline' => null]);
        $task = new Task(1);
        $this->assertFalse($task->isOverdue());
    }

    public function testGetTimeEstimateFormattedHoursAndMinutes(): void
    {
        $this->setupTaskLookup(['time_estimate' => 90]);
        $task = new Task(1);
        $this->assertEquals('1ч 30м', $task->getTimeEstimateFormatted());
    }

    public function testGetTimeEstimateFormattedHoursOnly(): void
    {
        $this->setupTaskLookup(['time_estimate' => 120]);
        $task = new Task(1);
        $this->assertEquals('2ч', $task->getTimeEstimateFormatted());
    }

    public function testGetTimeEstimateFormattedMinutesOnly(): void
    {
        $this->setupTaskLookup(['time_estimate' => 45]);
        $task = new Task(1);
        $this->assertEquals('45м', $task->getTimeEstimateFormatted());
    }

    public function testGetTimeEstimateFormattedZero(): void
    {
        $this->setupTaskLookup(['time_estimate' => 0]);
        $task = new Task(1);
        $this->assertEquals('', $task->getTimeEstimateFormatted());
    }

    public function testGetAssigneesReturnsArray(): void
    {
        $this->setupTaskLookup();
        DatabaseMock::setQueryResult(TASK_ASSIGNEES_TABLE, [
            ['staff_id' => 1, 'firstname' => 'John', 'lastname' => 'Doe', 'role' => 'assignee', 'assigned_date' => '2026-01-01'],
            ['staff_id' => 2, 'firstname' => 'Jane', 'lastname' => 'Smith', 'role' => 'assignee', 'assigned_date' => '2026-01-02'],
        ]);

        $task = new Task(1);
        $assignees = $task->getAssignees();
        $this->assertIsArray($assignees);
        $this->assertCount(2, $assignees);
        $this->assertEquals('John Doe', $assignees[0]['name']);
    }

    public function testGetAssigneesEmptyReturnsEmptyArray(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $assignees = $task->getAssignees();
        $this->assertIsArray($assignees);
        $this->assertEmpty($assignees);
    }

    public function testGetSubtasksReturnsArray(): void
    {
        $this->setupTaskLookup();
        DatabaseMock::setQueryResult('parent_task_id', [
            ['task_id' => 10, 'title' => 'Subtask 1'],
        ]);

        $task = new Task(1);
        $subtasks = $task->getSubtasks();
        $this->assertIsArray($subtasks);
        $this->assertCount(1, $subtasks);
    }

    public function testGetSubtasksEmptyReturnsEmptyArray(): void
    {
        $this->setupTaskLookup();
        $task = new Task(1);
        $subtasks = $task->getSubtasks();
        $this->assertIsArray($subtasks);
        $this->assertEmpty($subtasks);
    }

    public function testLookupReturnsTaskWhenFound(): void
    {
        $this->setupTaskLookup();
        $task = Task::lookup(1);
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(1, $task->getId());
    }

    public function testLookupReturnsNullWhenNotFound(): void
    {
        $task = Task::lookup(999);
        $this->assertNull($task);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(42);
        DatabaseMock::setQueryResult('SELECT t.*', [$this->makeTaskRow(['task_id' => 42])]);

        $errors = [];
        $result = Task::create($this->makeCreateData(), $errors);

        $this->assertNotFalse($result);
        $this->assertEmpty($errors);
    }

    public function testCreateWithAllFields(): void
    {
        DatabaseMock::setLastInsertId(43);
        DatabaseMock::setQueryResult('SELECT t.*', [$this->makeTaskRow(['task_id' => 43])]);

        $errors = [];
        $result = Task::create($this->makeCreateData([
            'title' => 'Detailed Task',
            'description' => 'Full description',
            'list_id' => 2,
            'task_type' => 'meeting',
            'priority' => 'high',
            'deadline' => '2026-12-31',
            'time_estimate' => 120,
        ]), $errors);

        $this->assertNotFalse($result);
        $this->assertEmpty($errors);
    }

    public function testCreateValidationEmptyTitle(): void
    {
        $errors = [];
        $result = Task::create($this->makeCreateData(['title' => '']), $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testCreateValidationMissingBoardId(): void
    {
        $errors = [];
        $result = Task::create($this->makeCreateData(['board_id' => 0]), $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testCreateValidationMissingCreatedBy(): void
    {
        $errors = [];
        $result = Task::create($this->makeCreateData(['created_by' => 0]), $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('err', $errors);
    }

    public function testUpdateSuccessful(): void
    {
        $this->setupTaskLookup();

        $errors = [];
        $result = Task::update(1, $this->makeCreateData([
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'priority' => 'high',
        ]), $errors);

        $this->assertTrue($result);
        $this->assertEmpty($errors);
    }

    public function testUpdateValidationEmptyTitle(): void
    {
        $this->setupTaskLookup();

        $errors = [];
        $result = Task::update(1, $this->makeCreateData(['title' => '']), $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testUpdateValidationMissingId(): void
    {
        $errors = [];
        $result = Task::update(0, $this->makeCreateData(), $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('err', $errors);
    }

    public function testDeleteSuccessful(): void
    {
        $this->setupTaskLookup();
        $result = Task::delete(1);
        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $result = Task::delete(999);
        $this->assertFalse($result);
    }

    public function testUpdateStatusSuccessful(): void
    {
        $this->setupTaskLookup(['status' => 'open']);

        $result = Task::updateStatus(1, 'in_progress', 1);
        $this->assertTrue($result);
    }

    public function testUpdateStatusToCompleted(): void
    {
        $this->setupTaskLookup(['status' => 'open']);

        $result = Task::updateStatus(1, 'completed', 1);
        $this->assertTrue($result);
    }

    public function testUpdateStatusInvalid(): void
    {
        $result = Task::updateStatus(1, 'invalid_status', 1);
        $this->assertFalse($result);
    }

    public function testMoveToListSuccessful(): void
    {
        $result = Task::moveToList(1, 5, 0);
        $this->assertTrue($result);
    }

    public function testMoveToListNoTaskId(): void
    {
        $result = Task::moveToList(0, 5);
        $this->assertFalse($result);
    }

    public function testArchiveSuccessful(): void
    {
        DatabaseMock::setAffectedRows(1);
        $result = Task::archive(1, 1);
        $this->assertTrue($result);
    }

    public function testArchiveNoTaskId(): void
    {
        $result = Task::archive(0, 1);
        $this->assertFalse($result);
    }

    public function testArchiveNoStaffId(): void
    {
        $result = Task::archive(1, 0);
        $this->assertFalse($result);
    }

    public function testUnarchiveSuccessful(): void
    {
        DatabaseMock::setAffectedRows(1);
        $result = Task::unarchive(1, 1);
        $this->assertTrue($result);
    }

    public function testUnarchiveNoTaskId(): void
    {
        $result = Task::unarchive(0, 1);
        $this->assertFalse($result);
    }

    public function testAddAssigneeSuccessful(): void
    {
        $result = Task::addAssignee(1, 5, 'assignee');
        $this->assertTrue($result);
    }

    public function testAddAssigneeNoTaskId(): void
    {
        $result = Task::addAssignee(0, 5);
        $this->assertFalse($result);
    }

    public function testAddAssigneeNoStaffId(): void
    {
        $result = Task::addAssignee(1, 0);
        $this->assertFalse($result);
    }

    public function testAddAssigneeInvalidRoleDefaultsToAssignee(): void
    {
        $result = Task::addAssignee(1, 5, 'invalid_role');
        $this->assertTrue($result);
    }

    public function testRemoveAssignee(): void
    {
        $result = Task::removeAssignee(1, 5, 'assignee');
        $this->assertTrue($result);
    }

    public function testGetStatusLabels(): void
    {
        $labels = Task::getStatusLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('open', $labels);
        $this->assertArrayHasKey('completed', $labels);
        $this->assertEquals('Открыта', $labels['open']);
    }

    public function testGetPriorityLabels(): void
    {
        $labels = Task::getPriorityLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('normal', $labels);
        $this->assertArrayHasKey('urgent', $labels);
        $this->assertEquals('Обычный', $labels['normal']);
    }

    public function testGetTaskTypeLabels(): void
    {
        $labels = Task::getTaskTypeLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('action', $labels);
        $this->assertArrayHasKey('meeting', $labels);
        $this->assertEquals('Действие', $labels['action']);
    }

    public function testUpdatePrioritySuccessful(): void
    {
        DatabaseMock::setQueryResult('SELECT priority', [['priority' => 'normal']]);

        $result = Task::updatePriority(1, 'high', 1);
        $this->assertTrue($result);
    }

    public function testUpdatePriorityInvalid(): void
    {
        $result = Task::updatePriority(1, 'invalid', 1);
        $this->assertFalse($result);
    }

    public function testUpdatePrioritySameValue(): void
    {
        DatabaseMock::setQueryResult('SELECT priority', [['priority' => 'normal']]);

        $result = Task::updatePriority(1, 'normal', 1);
        $this->assertTrue($result);
    }

    public function testGetAssigneeNames(): void
    {
        $this->setupTaskLookup();
        DatabaseMock::setQueryResult(TASK_ASSIGNEES_TABLE, [
            ['staff_id' => 1, 'firstname' => 'John', 'lastname' => 'Doe', 'role' => 'assignee', 'assigned_date' => '2026-01-01'],
        ]);

        $task = new Task(1);
        $names = $task->getAssigneeNames();
        $this->assertIsString($names);
        $this->assertStringContainsString('John Doe', $names);
    }

    public function testGetSubtaskCount(): void
    {
        $this->setupTaskLookup();
        DatabaseMock::setQueryResult('SELECT COUNT(*)', [['COUNT(*)' => 3]]);

        $task = new Task(1);
        $count = $task->getSubtaskCount();
        $this->assertEquals(3, $count);
    }
}
