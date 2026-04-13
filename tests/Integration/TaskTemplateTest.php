<?php

use PHPUnit\Framework\TestCase;

class TaskTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskTemplate')) {
            require_once INCLUDE_DIR . 'class.tasktemplate.php';
        }

        DatabaseMock::reset();
    }

    private function makeTemplateRow(array $overrides = []): array
    {
        return array_merge([
            'template_id' => 1,
            'template_name' => 'Bug Report Template',
            'template_type' => 'task',
            'template_data' => serialize([
                'title' => 'Bug Report',
                'description' => 'Describe the bug',
                'priority' => 'high',
            ]),
            'created_by' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
            'creator_name' => 'John Doe',
        ], $overrides);
    }

    private function setupTemplateLookup(array $overrides = []): void
    {
        $row = $this->makeTemplateRow($overrides);
        DatabaseMock::setQueryResult(TASK_TEMPLATES_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertEquals(1, $tpl->getId());
    }

    public function testConstructorNotFound(): void
    {
        $tpl = new TaskTemplate(999);
        $this->assertEquals(0, $tpl->getId());
    }

    public function testGetName(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertEquals('Bug Report Template', $tpl->getName());
    }

    public function testGetType(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertEquals('task', $tpl->getType());
    }

    public function testGetData(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $data = $tpl->getData();
        $this->assertIsArray($data);
        $this->assertEquals('Bug Report', $data['title']);
        $this->assertEquals('high', $data['priority']);
    }

    public function testGetDataInvalidSerialization(): void
    {
        $this->setupTemplateLookup(['template_data' => 'invalid']);
        $tpl = new TaskTemplate(1);
        $this->assertIsArray($tpl->getData());
        $this->assertEmpty($tpl->getData());
    }

    public function testGetCreatedBy(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertEquals(1, $tpl->getCreatedBy());
    }

    public function testGetCreatorName(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertEquals('John Doe', $tpl->getCreatorName());
    }

    public function testGetInfo(): void
    {
        $this->setupTemplateLookup();
        $tpl = new TaskTemplate(1);
        $this->assertIsArray($tpl->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupTemplateLookup();
        $tpl = TaskTemplate::lookup(1);
        $this->assertInstanceOf(TaskTemplate::class, $tpl);
    }

    public function testLookupNotFound(): void
    {
        $tpl = TaskTemplate::lookup(999);
        $this->assertNull($tpl);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = TaskTemplate::create([
            'template_name' => '',
            'template_type' => 'task',
            'created_by' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('template_name', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTemplate::create([
            'template_name' => 'New Template',
            'template_type' => 'task',
            'template_data' => ['title' => 'Test'],
            'created_by' => 1,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testCreateDefaultType(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskTemplate::create([
            'template_name' => 'Test',
            'template_type' => 'invalid',
            'created_by' => 1,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateNoId(): void
    {
        $errors = [];
        $result = TaskTemplate::update(0, ['template_name' => 'Test'], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskTemplate::update(1, [
            'template_name' => 'Updated',
            'template_type' => 'project',
        ], $errors);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $this->assertTrue(TaskTemplate::delete(1));
    }

    public function testGetTypeLabels(): void
    {
        $labels = TaskTemplate::getTypeLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('task', $labels);
        $this->assertArrayHasKey('project', $labels);
        $this->assertArrayHasKey('board', $labels);
        $this->assertCount(3, $labels);
    }

    public function testGetTypeLabel(): void
    {
        $this->setupTemplateLookup(['template_type' => 'task']);
        $tpl = new TaskTemplate(1);
        $this->assertEquals('Задача', $tpl->getTypeLabel());
    }

    public function testGetTypeLabelProject(): void
    {
        $this->setupTemplateLookup(['template_type' => 'project']);
        $tpl = new TaskTemplate(1);
        $this->assertEquals('Проект', $tpl->getTypeLabel());
    }

    public function testGetTypeLabelUnknown(): void
    {
        $this->setupTemplateLookup(['template_type' => 'custom']);
        $tpl = new TaskTemplate(1);
        $this->assertEquals('custom', $tpl->getTypeLabel());
    }
}
