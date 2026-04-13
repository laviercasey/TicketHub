<?php

use PHPUnit\Framework\TestCase;

class TaskPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskPermission')) {
            require_once INCLUDE_DIR . 'class.taskpermission.php';
        }

        DatabaseMock::reset();
        TaskPermission::$permissionCache = [];
        TaskPermission::$staffInfoCache = [];
        TaskPermission::$boardInfoCache = [];
    }

    private function makePermRow(array $overrides = []): array
    {
        return array_merge([
            'permission_id' => 1,
            'board_id' => 1,
            'staff_id' => 2,
            'dept_id' => null,
            'permission_level' => 'edit',
            'created' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupPermLookup(array $overrides = []): void
    {
        $row = $this->makePermRow($overrides);
        DatabaseMock::setQueryResult(TASK_BOARD_PERMS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupPermLookup();
        $perm = new TaskPermission(1);
        $this->assertEquals(1, $perm->getId());
    }

    public function testConstructorNotFound(): void
    {
        $perm = new TaskPermission(999);
        $this->assertEquals(0, $perm->getId());
    }

    public function testGetBoardId(): void
    {
        $this->setupPermLookup();
        $perm = new TaskPermission(1);
        $this->assertEquals(1, $perm->getBoardId());
    }

    public function testGetStaffId(): void
    {
        $this->setupPermLookup();
        $perm = new TaskPermission(1);
        $this->assertEquals(2, $perm->getStaffId());
    }

    public function testGetPermissionLevel(): void
    {
        $this->setupPermLookup();
        $perm = new TaskPermission(1);
        $this->assertEquals('edit', $perm->getPermissionLevel());
    }

    public function testGetInfo(): void
    {
        $this->setupPermLookup();
        $perm = new TaskPermission(1);
        $this->assertIsArray($perm->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupPermLookup();
        $perm = TaskPermission::lookup(1);
        $this->assertInstanceOf(TaskPermission::class, $perm);
    }

    public function testLookupNotFound(): void
    {
        $perm = TaskPermission::lookup(999);
        $this->assertNull($perm);
    }

    public function testCreateValidationNoBoardId(): void
    {
        $errors = [];
        $result = TaskPermission::create([
            'board_id' => 0,
            'staff_id' => 1,
            'dept_id' => 0,
            'permission_level' => 'view',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testCreateValidationNoTarget(): void
    {
        $errors = [];
        $result = TaskPermission::create([
            'board_id' => 1,
            'staff_id' => 0,
            'dept_id' => 0,
            'permission_level' => 'view',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('target', $errors);
    }

    public function testCreateValidationInvalidLevel(): void
    {
        $errors = [];
        $result = TaskPermission::create([
            'board_id' => 1,
            'staff_id' => 1,
            'dept_id' => 0,
            'permission_level' => 'invalid',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('permission_level', $errors);
    }

    public function testDelete(): void
    {
        $this->assertTrue(TaskPermission::delete(1));
    }

    public function testDeleteByBoardId(): void
    {
        $this->assertTrue(TaskPermission::deleteByBoardId(1));
    }

    public function testCanViewNoParams(): void
    {
        $this->assertFalse(TaskPermission::canView(0, 1));
        $this->assertFalse(TaskPermission::canView(1, 0));
    }

    public function testCanEditNoParams(): void
    {
        $this->assertFalse(TaskPermission::canEdit(0, 1));
        $this->assertFalse(TaskPermission::canEdit(1, 0));
    }

    public function testCanAdminNoParams(): void
    {
        $this->assertFalse(TaskPermission::canAdmin(0, 1));
        $this->assertFalse(TaskPermission::canAdmin(1, 0));
    }

    public function testGetLevelLabels(): void
    {
        $labels = TaskPermission::getLevelLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('view', $labels);
        $this->assertArrayHasKey('edit', $labels);
        $this->assertArrayHasKey('admin', $labels);
        $this->assertCount(3, $labels);
    }

    public function testPermissionCacheWorks(): void
    {
        TaskPermission::$permissionCache['view_1_1'] = true;
        $this->assertTrue(TaskPermission::canView(1, 1));
    }

    public function testPermissionCacheEdit(): void
    {
        TaskPermission::$permissionCache['edit_1_1'] = false;
        $this->assertFalse(TaskPermission::canEdit(1, 1));
    }

    public function testPermissionCacheAdmin(): void
    {
        TaskPermission::$permissionCache['admin_1_1'] = true;
        $this->assertTrue(TaskPermission::canAdmin(1, 1));
    }
}
