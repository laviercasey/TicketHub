<?php

use PHPUnit\Framework\TestCase;

class TaskCommentTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskComment')) {
            require_once INCLUDE_DIR . 'class.taskcomment.php';
        }

        DatabaseMock::reset();
    }

    private function makeCommentRow(array $overrides = []): array
    {
        return array_merge([
            'comment_id' => 1,
            'task_id' => 1,
            'staff_id' => 1,
            'comment_text' => 'This is a comment',
            'created' => '2026-01-15 10:00:00',
            'updated' => '2026-01-15 10:00:00',
            'staff_name' => 'John Doe',
        ], $overrides);
    }

    private function setupCommentLookup(array $overrides = []): void
    {
        $row = $this->makeCommentRow($overrides);
        DatabaseMock::setQueryResult(TASK_COMMENTS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals(1, $comment->getId());
    }

    public function testConstructorNotFound(): void
    {
        $comment = new TaskComment(999);
        $this->assertEquals(0, $comment->getId());
    }

    public function testGetTaskId(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals(1, $comment->getTaskId());
    }

    public function testGetStaffId(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals(1, $comment->getStaffId());
    }

    public function testGetText(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals('This is a comment', $comment->getText());
    }

    public function testGetCreated(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals('2026-01-15 10:00:00', $comment->getCreated());
    }

    public function testGetUpdated(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals('2026-01-15 10:00:00', $comment->getUpdated());
    }

    public function testGetStaffName(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $this->assertEquals('John Doe', $comment->getStaffName());
    }

    public function testGetInfo(): void
    {
        $this->setupCommentLookup();
        $comment = new TaskComment(1);
        $info = $comment->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('This is a comment', $info['comment_text']);
    }

    public function testLookupFound(): void
    {
        $this->setupCommentLookup();
        $comment = TaskComment::lookup(1);
        $this->assertInstanceOf(TaskComment::class, $comment);
    }

    public function testLookupNotFound(): void
    {
        $comment = TaskComment::lookup(999);
        $this->assertNull($comment);
    }

    public function testGetByTaskId(): void
    {
        DatabaseMock::setQueryResult(TASK_COMMENTS_TABLE, [
            $this->makeCommentRow(),
            $this->makeCommentRow(['comment_id' => 2, 'comment_text' => 'Another comment']),
        ]);
        $comments = TaskComment::getByTaskId(1);
        $this->assertIsArray($comments);
        $this->assertCount(2, $comments);
    }

    public function testGetCountByTaskId(): void
    {
        DatabaseMock::setQueryResult('SELECT COUNT(*)', [['COUNT(*)' => 5]]);
        $count = TaskComment::getCountByTaskId(1);
        $this->assertEquals(5, $count);
    }

    public function testCreateValidationNoTaskId(): void
    {
        $errors = [];
        $result = TaskComment::create([
            'task_id' => 0,
            'staff_id' => 1,
            'comment_text' => 'Test',
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateValidationNoText(): void
    {
        $errors = [];
        $result = TaskComment::create([
            'task_id' => 1,
            'staff_id' => 1,
            'comment_text' => '',
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(10);
        $errors = [];
        $result = TaskComment::create([
            'task_id' => 1,
            'staff_id' => 1,
            'comment_text' => 'Test comment',
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateValidationNoText(): void
    {
        $errors = [];
        $result = TaskComment::update(1, '', $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskComment::update(1, 'Updated comment', $errors);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $result = TaskComment::delete(1);
        $this->assertTrue($result);
    }
}
