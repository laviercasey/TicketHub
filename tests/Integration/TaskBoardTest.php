<?php

use PHPUnit\Framework\TestCase;

class TaskBoardTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskBoard')) {
            require_once INCLUDE_DIR . 'class.taskboard.php';
        }

        DatabaseMock::reset();
    }

    private function makeBoardRow(array $overrides = []): array
    {
        return array_merge([
            'board_id' => 1,
            'board_name' => 'Test Board',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => 'Test Description',
            'color' => '#3498db',
            'is_archived' => 0,
            'created_by' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupBoardLookup(array $overrides = []): void
    {
        $row = $this->makeBoardRow($overrides);
        DatabaseMock::setQueryResult(TASK_BOARDS_TABLE, [$row]);
    }

    public function testConstructorLoadsBoard(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals(1, $board->getId());
    }

    public function testConstructorNotFound(): void
    {
        $board = new TaskBoard(999);
        $this->assertEquals(0, $board->getId());
    }

    public function testGetName(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals('Test Board', $board->getName());
    }

    public function testGetType(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals('project', $board->getType());
    }

    public function testGetDeptId(): void
    {
        $this->setupBoardLookup(['dept_id' => 5]);
        $board = new TaskBoard(1);
        $this->assertEquals(5, $board->getDeptId());
    }

    public function testGetDescription(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals('Test Description', $board->getDescription());
    }

    public function testGetColor(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals('#3498db', $board->getColor());
    }

    public function testIsArchived(): void
    {
        $this->setupBoardLookup(['is_archived' => 1]);
        $board = new TaskBoard(1);
        $this->assertTrue($board->isArchived());
    }

    public function testIsNotArchived(): void
    {
        $this->setupBoardLookup(['is_archived' => 0]);
        $board = new TaskBoard(1);
        $this->assertFalse($board->isArchived());
    }

    public function testIsDepartment(): void
    {
        $this->setupBoardLookup(['board_type' => 'department']);
        $board = new TaskBoard(1);
        $this->assertTrue($board->isDepartment());
        $this->assertFalse($board->isProject());
    }

    public function testIsProject(): void
    {
        $this->setupBoardLookup(['board_type' => 'project']);
        $board = new TaskBoard(1);
        $this->assertTrue($board->isProject());
        $this->assertFalse($board->isDepartment());
    }

    public function testGetTypeLabelDepartment(): void
    {
        $this->setupBoardLookup(['board_type' => 'department']);
        $board = new TaskBoard(1);
        $this->assertEquals('Отдел', $board->getTypeLabel());
    }

    public function testGetTypeLabelProject(): void
    {
        $this->setupBoardLookup(['board_type' => 'project']);
        $board = new TaskBoard(1);
        $this->assertEquals('Проект', $board->getTypeLabel());
    }

    public function testGetTypeLabelUnknown(): void
    {
        $this->setupBoardLookup(['board_type' => 'custom']);
        $board = new TaskBoard(1);
        $this->assertEquals('custom', $board->getTypeLabel());
    }

    public function testGetCreatedBy(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $this->assertEquals(1, $board->getCreatedBy());
    }

    public function testGetInfo(): void
    {
        $this->setupBoardLookup();
        $board = new TaskBoard(1);
        $info = $board->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('Test Board', $info['board_name']);
    }

    public function testGetTaskCount(): void
    {
        $this->setupBoardLookup();
        DatabaseMock::setQueryResult('SELECT COUNT(*)', [['COUNT(*)' => 5]]);
        $board = new TaskBoard(1);
        $this->assertEquals(5, $board->getTaskCount());
    }

    public function testGetLists(): void
    {
        $this->setupBoardLookup();
        DatabaseMock::setQueryResult(TASK_LISTS_TABLE, [
            ['list_id' => 1, 'list_name' => 'To Do', 'list_order' => 0],
            ['list_id' => 2, 'list_name' => 'Done', 'list_order' => 1],
        ]);
        $board = new TaskBoard(1);
        $lists = $board->getLists();
        $this->assertIsArray($lists);
        $this->assertCount(2, $lists);
    }

    public function testLookupFound(): void
    {
        $this->setupBoardLookup();
        $board = TaskBoard::lookup(1);
        $this->assertInstanceOf(TaskBoard::class, $board);
    }

    public function testLookupNotFound(): void
    {
        $board = TaskBoard::lookup(999);
        $this->assertNull($board);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(10);
        $errors = [];
        $result = TaskBoard::create([
            'board_name' => 'New Board',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => '',
            'color' => '#3498db',
            'created_by' => 1,
        ], $errors);
        $this->assertNotFalse($result);
        $this->assertEmpty($errors);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        TaskBoard::create([
            'board_name' => '',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => '',
            'color' => '',
            'created_by' => 1,
        ], $errors);
        $this->assertArrayHasKey('board_name', $errors);
    }

    public function testCreateValidationInvalidType(): void
    {
        $errors = [];
        TaskBoard::create([
            'board_name' => 'Board',
            'board_type' => 'invalid',
            'dept_id' => 0,
            'description' => '',
            'color' => '',
            'created_by' => 1,
        ], $errors);
        $this->assertArrayHasKey('board_type', $errors);
    }

    public function testCreateValidationDeptRequiredForDepartmentType(): void
    {
        $errors = [];
        TaskBoard::create([
            'board_name' => 'Board',
            'board_type' => 'department',
            'dept_id' => 0,
            'description' => '',
            'color' => '',
            'created_by' => 1,
        ], $errors);
        $this->assertArrayHasKey('dept_id', $errors);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskBoard::update(1, [
            'board_name' => 'Updated',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => 'Updated desc',
            'color' => '#ff0000',
        ], $errors);
        $this->assertTrue($result);
    }

    public function testUpdateValidationNoName(): void
    {
        $errors = [];
        TaskBoard::update(1, [
            'board_name' => '',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => '',
            'color' => '',
        ], $errors);
        $this->assertArrayHasKey('board_name', $errors);
    }

    public function testUpdateValidationNoId(): void
    {
        $errors = [];
        TaskBoard::update(0, [
            'board_name' => 'Board',
            'board_type' => 'project',
            'dept_id' => 0,
            'description' => '',
            'color' => '',
        ], $errors);
        $this->assertArrayHasKey('err', $errors);
    }

    public function testDeleteSuccessful(): void
    {
        $this->setupBoardLookup();
        $result = TaskBoard::delete(1);
        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $result = TaskBoard::delete(999);
        $this->assertFalse($result);
    }

    public function testArchive(): void
    {
        $result = TaskBoard::archive(1);
        $this->assertTrue($result);
    }

    public function testAddListSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskBoard::addList(1, 'New List', $errors);
        $this->assertNotFalse($result);
    }

    public function testAddListValidationNoName(): void
    {
        $errors = [];
        TaskBoard::addList(1, '', $errors);
        $this->assertArrayHasKey('list_name', $errors);
    }

    public function testUpdateList(): void
    {
        $result = TaskBoard::updateList(1, 'Updated List');
        $this->assertTrue($result);
    }

    public function testDeleteList(): void
    {
        $result = TaskBoard::deleteList(1);
        $this->assertTrue($result);
    }

    public function testReorderLists(): void
    {
        $result = TaskBoard::reorderLists(1, [3, 1, 2]);
        $this->assertTrue($result);
    }

    public function testReorderListsInvalidInput(): void
    {
        $result = TaskBoard::reorderLists(1, 'not-array');
        $this->assertFalse($result);
    }
}
