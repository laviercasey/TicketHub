<?php

use PHPUnit\Framework\TestCase;

class TopicTest extends TestCase
{
    private array $topicRow;

    protected function setUp(): void
    {
        if (!class_exists('Topic')) {
            require_once INCLUDE_DIR . 'class.topic.php';
        }

        $this->topicRow = [
            'topic_id' => 1,
            'topic' => 'General Support',
            'dept_id' => 1,
            'priority_id' => 2,
            'isactive' => 1,
            'noautoresp' => 0,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ];

        DatabaseMock::reset();
    }

    private function setupTopicLookup(array $overrides = []): void
    {
        $row = array_merge($this->topicRow, $overrides);
        DatabaseMock::setQueryResult('SELECT * FROM ' . TOPIC_TABLE, [$row]);
    }

    public function testLoadTopic(): void
    {
        $this->setupTopicLookup();
        $topic = new Topic(1);
        $this->assertEquals(1, $topic->getId());
    }

    public function testLoadTopicNotFound(): void
    {
        $topic = new Topic(999);
        $this->assertEquals(0, $topic->getId());
    }

    public function testGetName(): void
    {
        $this->setupTopicLookup();
        $topic = new Topic(1);
        $this->assertEquals('General Support', $topic->getName());
    }

    public function testGetDeptId(): void
    {
        $this->setupTopicLookup();
        $topic = new Topic(1);
        $this->assertEquals(1, $topic->getDeptId());
    }

    public function testGetPriorityId(): void
    {
        $this->setupTopicLookup();
        $topic = new Topic(1);
        $this->assertEquals(2, $topic->getPriorityId());
    }

    public function testAutoRespond(): void
    {
        $this->setupTopicLookup(['noautoresp' => 0]);
        $topic = new Topic(1);
        $this->assertTrue($topic->autoRespond());
    }

    public function testAutoRespondDisabled(): void
    {
        $this->setupTopicLookup(['noautoresp' => 1]);
        $topic = new Topic(1);
        $this->assertFalse($topic->autoRespond());
    }

    public function testIsEnabled(): void
    {
        $this->setupTopicLookup(['isactive' => 1]);
        $topic = new Topic(1);
        $this->assertTrue($topic->isEnabled());
    }

    public function testIsDisabled(): void
    {
        $this->setupTopicLookup(['isactive' => 0]);
        $topic = new Topic(1);
        $this->assertFalse($topic->isEnabled());
    }

    public function testIsActiveAlias(): void
    {
        $this->setupTopicLookup(['isactive' => 1]);
        $topic = new Topic(1);
        $this->assertEquals($topic->isEnabled(), $topic->isActive());
    }

    public function testGetInfo(): void
    {
        $this->setupTopicLookup();
        $topic = new Topic(1);
        $info = $topic->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('General Support', $info['topic']);
    }

    public function testConstructorWithFetchFalse(): void
    {
        $topic = new Topic(5, false);
        $this->assertEquals(5, $topic->getId());
        $this->assertNull($topic->getName());
    }

    public function testConstructorWithZeroId(): void
    {
        $topic = new Topic(0);
        $this->assertEquals(0, $topic->getId());
    }

    public function testCreateValidationNoTopic(): void
    {
        $errors = [];
        $result = Topic::create([
            'dept_id' => 1,
            'priority_id' => 2,
            'isactive' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('topic', $errors);
    }

    public function testCreateValidationTopicTooShort(): void
    {
        $errors = [];
        $result = Topic::create([
            'topic' => 'Hi',
            'dept_id' => 1,
            'priority_id' => 2,
            'isactive' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('topic', $errors);
    }

    public function testCreateValidationNoDept(): void
    {
        $errors = [];
        $result = Topic::create([
            'topic' => 'General Support',
            'priority_id' => 2,
            'isactive' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('dept_id', $errors);
    }

    public function testCreateValidationNoPriority(): void
    {
        $errors = [];
        $result = Topic::create([
            'topic' => 'General Support',
            'dept_id' => 1,
            'isactive' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('priority_id', $errors);
    }

    public function testCreateValidationIdMismatch(): void
    {
        $errors = [];
        $result = Topic::save(5, [
            'topic_id' => 3,
            'topic' => 'General Support',
            'dept_id' => 1,
            'priority_id' => 2,
            'isactive' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('err', $errors);
    }
}
