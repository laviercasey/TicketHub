<?php

use PHPUnit\Framework\TestCase;

class TaskFilterTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskFilter')) {
            require_once INCLUDE_DIR . 'class.taskfilter.php';
        }

        DatabaseMock::reset();
    }

    private function makeFilterRow(array $overrides = []): array
    {
        return array_merge([
            'filter_id' => 1,
            'staff_id' => 1,
            'filter_name' => 'My Open Tasks',
            'filter_config' => serialize(['status' => ['open', 'in_progress']]),
            'is_default' => 0,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupFilterLookup(array $overrides = []): void
    {
        $row = $this->makeFilterRow($overrides);
        DatabaseMock::setQueryResult(TASK_SAVED_FILTERS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupFilterLookup();
        $filter = new TaskFilter(1);
        $this->assertEquals(1, $filter->getId());
    }

    public function testConstructorNotFound(): void
    {
        $filter = new TaskFilter(999);
        $this->assertEquals(0, $filter->getId());
    }

    public function testGetName(): void
    {
        $this->setupFilterLookup();
        $filter = new TaskFilter(1);
        $this->assertEquals('My Open Tasks', $filter->getName());
    }

    public function testGetStaffId(): void
    {
        $this->setupFilterLookup();
        $filter = new TaskFilter(1);
        $this->assertEquals(1, $filter->getStaffId());
    }

    public function testIsDefault(): void
    {
        $this->setupFilterLookup(['is_default' => 1]);
        $filter = new TaskFilter(1);
        $this->assertTrue($filter->isDefault());
    }

    public function testIsNotDefault(): void
    {
        $this->setupFilterLookup(['is_default' => 0]);
        $filter = new TaskFilter(1);
        $this->assertFalse($filter->isDefault());
    }

    public function testGetConfig(): void
    {
        $this->setupFilterLookup();
        $filter = new TaskFilter(1);
        $config = $filter->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('status', $config);
    }

    public function testGetConfigInvalid(): void
    {
        $this->setupFilterLookup(['filter_config' => 'invalid']);
        $filter = new TaskFilter(1);
        $this->assertIsArray($filter->getConfig());
        $this->assertEmpty($filter->getConfig());
    }

    public function testGetInfo(): void
    {
        $this->setupFilterLookup();
        $filter = new TaskFilter(1);
        $this->assertIsArray($filter->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupFilterLookup();
        $filter = TaskFilter::lookup(1);
        $this->assertInstanceOf(TaskFilter::class, $filter);
    }

    public function testLookupNotFound(): void
    {
        $filter = TaskFilter::lookup(999);
        $this->assertNull($filter);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = TaskFilter::create([
            'filter_name' => '',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('filter_name', $errors);
    }

    public function testCreateValidationNoStaffId(): void
    {
        $errors = [];
        $result = TaskFilter::create([
            'filter_name' => 'Test',
            'staff_id' => 0,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('staff_id', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskFilter::create([
            'filter_name' => 'New Filter',
            'staff_id' => 1,
            'filter_config' => ['status' => ['open']],
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateValidationNoName(): void
    {
        $errors = [];
        $result = TaskFilter::update(1, ['filter_name' => ''], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskFilter::update(1, [
            'filter_name' => 'Updated Filter',
            'filter_config' => ['status' => ['open', 'closed']],
        ], $errors);
        $this->assertTrue($result);
    }

    public function testUpdateZeroId(): void
    {
        $errors = [];
        $result = TaskFilter::update(0, ['filter_name' => 'Test'], $errors);
        $this->assertFalse($result);
    }

    public function testDelete(): void
    {
        $this->assertTrue(TaskFilter::delete(1));
    }

    public function testDeleteZeroId(): void
    {
        $this->assertFalse(TaskFilter::delete(0));
    }

    public function testGetByStaffEmpty(): void
    {
        $this->assertEmpty(TaskFilter::getByStaff(0));
    }

    public function testSetDefaultNoParams(): void
    {
        $this->assertFalse(TaskFilter::setDefault(0, 1));
        $this->assertFalse(TaskFilter::setDefault(1, 0));
    }

    public function testSetDefault(): void
    {
        $this->assertTrue(TaskFilter::setDefault(1, 1));
    }

    public function testUnsetDefault(): void
    {
        $this->assertTrue(TaskFilter::unsetDefault(1, 1));
    }

    public function testUnsetDefaultNoParams(): void
    {
        $this->assertFalse(TaskFilter::unsetDefault(0, 1));
    }

    public function testGetDefaultNoStaffId(): void
    {
        $this->assertNull(TaskFilter::getDefault(0));
    }

    public function testApplyFilterEmpty(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter([], $qwhere, $qfrom);
        $this->assertEquals('', $qwhere);
        $this->assertEquals('', $qfrom);
    }

    public function testApplyFilterBoardId(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['board_id' => 5], $qwhere, $qfrom);
        $this->assertStringContainsString('board_id', $qwhere);
    }

    public function testApplyFilterStatus(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['status' => ['open', 'closed']], $qwhere, $qfrom);
        $this->assertStringContainsString('status IN', $qwhere);
    }

    public function testApplyFilterPriority(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['priority' => ['high', 'urgent']], $qwhere, $qfrom);
        $this->assertStringContainsString('priority IN', $qwhere);
    }

    public function testApplyFilterAssignee(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['assignee' => [1, 2]], $qwhere, $qfrom);
        $this->assertStringContainsString('staff_id IN', $qwhere);
        $this->assertStringContainsString('JOIN', $qfrom);
    }

    public function testApplyFilterDateRange(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter([
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ], $qwhere, $qfrom);
        $this->assertStringContainsString('created >=', $qwhere);
        $this->assertStringContainsString('created <=', $qwhere);
    }

    public function testApplyFilterHasDeadline(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['has_deadline' => true], $qwhere, $qfrom);
        $this->assertStringContainsString('deadline IS NOT NULL', $qwhere);
    }

    public function testApplyFilterIsOverdue(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['is_overdue' => true], $qwhere, $qfrom);
        $this->assertStringContainsString('deadline', $qwhere);
        $this->assertStringContainsString('NOW()', $qwhere);
    }

    public function testApplyFilterTags(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['tags' => [1, 2, 3]], $qwhere, $qfrom);
        $this->assertStringContainsString('tag_id IN', $qwhere);
        $this->assertStringContainsString('JOIN', $qfrom);
    }

    public function testApplyFilterSearchText(): void
    {
        $qwhere = '';
        $qfrom = '';
        TaskFilter::applyFilter(['search_text' => 'bug fix'], $qwhere, $qfrom);
        $this->assertStringContainsString('MATCH', $qwhere);
        $this->assertStringContainsString('AGAINST', $qwhere);
    }
}
