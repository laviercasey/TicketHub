<?php

use PHPUnit\Framework\TestCase;

class TaskTagTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskTag')) {
            require_once INCLUDE_DIR . 'class.tasktag.php';
        }

        DatabaseMock::reset();
    }

    private function makeTagRow(array $overrides = []): array
    {
        return array_merge([
            'tag_id' => 1,
            'tag_name' => 'Bug',
            'tag_color' => '#e74c3c',
            'board_id' => 1,
            'created' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupTagLookup(array $overrides = []): void
    {
        $row = $this->makeTagRow($overrides);
        DatabaseMock::setQueryResult(TASK_TAGS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupTagLookup();
        $tag = new TaskTag(1);
        $this->assertEquals(1, $tag->getId());
    }

    public function testConstructorNotFound(): void
    {
        $tag = new TaskTag(999);
        $this->assertEquals(0, $tag->getId());
    }

    public function testGetName(): void
    {
        $this->setupTagLookup();
        $tag = new TaskTag(1);
        $this->assertEquals('Bug', $tag->getName());
    }

    public function testGetColor(): void
    {
        $this->setupTagLookup();
        $tag = new TaskTag(1);
        $this->assertEquals('#e74c3c', $tag->getColor());
    }

    public function testGetBoardId(): void
    {
        $this->setupTagLookup();
        $tag = new TaskTag(1);
        $this->assertEquals(1, $tag->getBoardId());
    }

    public function testGetInfo(): void
    {
        $this->setupTagLookup();
        $tag = new TaskTag(1);
        $this->assertIsArray($tag->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupTagLookup();
        $tag = TaskTag::lookup(1);
        $this->assertInstanceOf(TaskTag::class, $tag);
    }

    public function testLookupNotFound(): void
    {
        $tag = TaskTag::lookup(999);
        $this->assertNull($tag);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = TaskTag::create([
            'tag_name' => '',
            'board_id' => 1,
            'tag_color' => '#ff0000',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('tag_name', $errors);
    }

    public function testCreateValidationNoBoardId(): void
    {
        $errors = [];
        $result = TaskTag::create([
            'tag_name' => 'Test',
            'board_id' => 0,
            'tag_color' => '#ff0000',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTag::create([
            'tag_name' => 'Feature',
            'board_id' => 1,
            'tag_color' => '#2ecc71',
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testCreateDefaultColor(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTag::create([
            'tag_name' => 'Feature',
            'board_id' => 1,
            'tag_color' => '',
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testCreateInvalidColorFallsBackToDefault(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTag::create([
            'tag_name' => 'Feature',
            'board_id' => 1,
            'tag_color' => 'invalid-color',
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateTagValidationNoName(): void
    {
        $errors = [];
        $result = TaskTag::updateTag(1, ['tag_name' => '', 'tag_color' => '#ff0000'], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('tag_name', $errors);
    }

    public function testUpdateTagSuccessful(): void
    {
        $errors = [];
        $result = TaskTag::updateTag(1, ['tag_name' => 'Updated', 'tag_color' => '#00ff00'], $errors);
        $this->assertTrue($result);
    }

    public function testUpdateTagZeroId(): void
    {
        $errors = [];
        $result = TaskTag::updateTag(0, ['tag_name' => 'Test', 'tag_color' => '#ff0000'], $errors);
        $this->assertFalse($result);
    }

    public function testDeleteTag(): void
    {
        $this->assertTrue(TaskTag::deleteTag(1));
    }

    public function testDeleteTagZeroId(): void
    {
        $this->assertFalse(TaskTag::deleteTag(0));
    }

    public function testAddToTask(): void
    {
        $this->assertTrue(TaskTag::addToTask(1, 1));
    }

    public function testAddToTaskNoTaskId(): void
    {
        $this->assertFalse(TaskTag::addToTask(0, 1));
    }

    public function testAddToTaskNoTagId(): void
    {
        $this->assertFalse(TaskTag::addToTask(1, 0));
    }

    public function testRemoveFromTask(): void
    {
        $this->assertTrue(TaskTag::removeFromTask(1, 1));
    }

    public function testRemoveFromTaskNoTaskId(): void
    {
        $this->assertFalse(TaskTag::removeFromTask(0, 1));
    }

    public function testGetByTaskEmpty(): void
    {
        $this->assertEmpty(TaskTag::getByTask(0));
    }

    public function testGetByBoardEmpty(): void
    {
        $this->assertEmpty(TaskTag::getByBoard(0));
    }

    public function testSetTaskTagsNoTaskId(): void
    {
        $this->assertFalse(TaskTag::setTaskTags(0, [1, 2]));
    }

    public function testSetTaskTagsEmptyArray(): void
    {
        $this->assertTrue(TaskTag::setTaskTags(1, []));
    }

    public function testSetTaskTagsWithIds(): void
    {
        $this->assertTrue(TaskTag::setTaskTags(1, [1, 2, 3]));
    }

    public function testDeleteByTaskId(): void
    {
        $this->assertTrue(TaskTag::deleteByTaskId(1));
    }

    public function testDeleteByTaskIdZero(): void
    {
        $this->assertFalse(TaskTag::deleteByTaskId(0));
    }
}
