<?php

use PHPUnit\Framework\TestCase;

class TaskCustomFieldTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskCustomField')) {
            require_once INCLUDE_DIR . 'class.taskcustomfield.php';
        }

        DatabaseMock::reset();
    }

    private function makeFieldRow(array $overrides = []): array
    {
        return array_merge([
            'field_id' => 1,
            'board_id' => 1,
            'field_name' => 'Priority Level',
            'field_type' => 'dropdown',
            'field_options' => serialize(['Low', 'Medium', 'High']),
            'is_required' => 0,
            'field_order' => 0,
            'created' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupFieldLookup(array $overrides = []): void
    {
        $row = $this->makeFieldRow($overrides);
        DatabaseMock::setQueryResult(TASK_CUSTOM_FIELDS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $this->assertEquals(1, $field->getId());
    }

    public function testConstructorNotFound(): void
    {
        $field = new TaskCustomField(999);
        $this->assertEquals(0, $field->getId());
    }

    public function testGetName(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $this->assertEquals('Priority Level', $field->getName());
    }

    public function testGetType(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $this->assertEquals('dropdown', $field->getType());
    }

    public function testGetBoardId(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $this->assertEquals(1, $field->getBoardId());
    }

    public function testIsRequired(): void
    {
        $this->setupFieldLookup(['is_required' => 1]);
        $field = new TaskCustomField(1);
        $this->assertTrue($field->isRequired());
    }

    public function testIsNotRequired(): void
    {
        $this->setupFieldLookup(['is_required' => 0]);
        $field = new TaskCustomField(1);
        $this->assertFalse($field->isRequired());
    }

    public function testGetOrder(): void
    {
        $this->setupFieldLookup(['field_order' => 5]);
        $field = new TaskCustomField(1);
        $this->assertEquals(5, $field->getOrder());
    }

    public function testGetOptions(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $options = $field->getOptions();
        $this->assertIsArray($options);
        $this->assertCount(3, $options);
        $this->assertContains('High', $options);
    }

    public function testGetOptionsEmpty(): void
    {
        $this->setupFieldLookup(['field_options' => '']);
        $field = new TaskCustomField(1);
        $this->assertEmpty($field->getOptions());
    }

    public function testGetInfo(): void
    {
        $this->setupFieldLookup();
        $field = new TaskCustomField(1);
        $this->assertIsArray($field->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupFieldLookup();
        $field = TaskCustomField::lookup(1);
        $this->assertInstanceOf(TaskCustomField::class, $field);
    }

    public function testLookupNotFound(): void
    {
        $field = TaskCustomField::lookup(999);
        $this->assertNull($field);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = TaskCustomField::create([
            'field_name' => '',
            'board_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('field_name', $errors);
    }

    public function testCreateValidationNoBoardId(): void
    {
        $errors = [];
        $result = TaskCustomField::create([
            'field_name' => 'Test',
            'board_id' => 0,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setQueryResult('MAX(field_order)', [['MAX(field_order)' => 2]]);
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskCustomField::create([
            'field_name' => 'Test Field',
            'board_id' => 1,
            'field_type' => 'text',
            'is_required' => 0,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testCreateWithDropdownOptions(): void
    {
        DatabaseMock::setQueryResult('MAX(field_order)', [['MAX(field_order)' => 0]]);
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = TaskCustomField::create([
            'field_name' => 'Status',
            'board_id' => 1,
            'field_type' => 'dropdown',
            'field_options' => ['Open', 'Closed'],
            'is_required' => 1,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateFieldValidationNoName(): void
    {
        $errors = [];
        $result = TaskCustomField::updateField(1, ['field_name' => ''], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('field_name', $errors);
    }

    public function testUpdateFieldSuccessful(): void
    {
        $errors = [];
        $result = TaskCustomField::updateField(1, [
            'field_name' => 'Updated',
            'is_required' => 1,
        ], $errors);
        $this->assertTrue($result);
    }

    public function testDeleteField(): void
    {
        $this->assertTrue(TaskCustomField::deleteField(1));
    }

    public function testDeleteFieldZeroId(): void
    {
        $this->assertFalse(TaskCustomField::deleteField(0));
    }

    public function testGetByBoardEmpty(): void
    {
        $this->assertEmpty(TaskCustomField::getByBoard(0));
    }

    public function testGetValueEmpty(): void
    {
        $this->assertEquals('', TaskCustomField::getValue(0, 1));
    }

    public function testSetValueNoTaskId(): void
    {
        $this->assertFalse(TaskCustomField::setValue(0, 1, 'test'));
    }

    public function testSetValueNoFieldId(): void
    {
        $this->assertFalse(TaskCustomField::setValue(1, 0, 'test'));
    }

    public function testGetValuesByTaskEmpty(): void
    {
        $this->assertEmpty(TaskCustomField::getValuesByTask(0));
    }

    public function testSetTaskValuesNoTaskId(): void
    {
        $this->assertFalse(TaskCustomField::setTaskValues(0, ['1' => 'val']));
    }

    public function testSetTaskValuesNotArray(): void
    {
        $this->assertFalse(TaskCustomField::setTaskValues(1, 'not-array'));
    }

    public function testDeleteByTaskId(): void
    {
        $this->assertTrue(TaskCustomField::deleteByTaskId(1));
    }

    public function testDeleteByTaskIdZero(): void
    {
        $this->assertFalse(TaskCustomField::deleteByTaskId(0));
    }

    public function testGetTypeLabels(): void
    {
        $labels = TaskCustomField::getTypeLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('text', $labels);
        $this->assertArrayHasKey('dropdown', $labels);
        $this->assertArrayHasKey('checkbox', $labels);
        $this->assertCount(7, $labels);
    }

    public function testFormatValueCheckboxYes(): void
    {
        $this->assertEquals('Да', TaskCustomField::formatValue('checkbox', '1', ''));
    }

    public function testFormatValueCheckboxNo(): void
    {
        $this->assertEquals('Нет', TaskCustomField::formatValue('checkbox', '0', ''));
    }

    public function testFormatValueDate(): void
    {
        $result = TaskCustomField::formatValue('date', '2026-03-15', '');
        $this->assertEquals('15.03.2026', $result);
    }

    public function testFormatValueEmpty(): void
    {
        $this->assertEquals('', TaskCustomField::formatValue('text', '', ''));
    }

    public function testFormatValueDefault(): void
    {
        $this->assertEquals('hello', TaskCustomField::formatValue('text', 'hello', ''));
    }
}
