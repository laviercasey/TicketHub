<?php

use PHPUnit\Framework\TestCase;

class TaskAutomationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('TaskAutomation')) {
            require_once INCLUDE_DIR . 'class.taskautomation.php';
        }

        DatabaseMock::reset();
    }

    private function makeRuleRow(array $overrides = []): array
    {
        return array_merge([
            'rule_id' => 1,
            'board_id' => 1,
            'rule_name' => 'Auto close',
            'trigger_type' => 'status_changed',
            'trigger_config' => serialize(['to_status' => 'completed']),
            'action_type' => 'change_status',
            'action_config' => serialize(['status' => 'completed']),
            'is_enabled' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupRuleLookup(array $overrides = []): void
    {
        $row = $this->makeRuleRow($overrides);
        DatabaseMock::setQueryResult(TASK_AUTOMATION_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertEquals(1, $rule->getId());
    }

    public function testConstructorNotFound(): void
    {
        $rule = new TaskAutomation(999);
        $this->assertEquals(0, $rule->getId());
    }

    public function testGetName(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertEquals('Auto close', $rule->getName());
    }

    public function testGetBoardId(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertEquals(1, $rule->getBoardId());
    }

    public function testGetTriggerType(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertEquals('status_changed', $rule->getTriggerType());
    }

    public function testGetActionType(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertEquals('change_status', $rule->getActionType());
    }

    public function testIsEnabled(): void
    {
        $this->setupRuleLookup(['is_enabled' => 1]);
        $rule = new TaskAutomation(1);
        $this->assertTrue($rule->isEnabled());
    }

    public function testIsDisabled(): void
    {
        $this->setupRuleLookup(['is_enabled' => 0]);
        $rule = new TaskAutomation(1);
        $this->assertFalse($rule->isEnabled());
    }

    public function testGetTriggerConfig(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $config = $rule->getTriggerConfig();
        $this->assertIsArray($config);
        $this->assertEquals('completed', $config['to_status']);
    }

    public function testGetTriggerConfigEmpty(): void
    {
        $this->setupRuleLookup(['trigger_config' => '']);
        $rule = new TaskAutomation(1);
        $this->assertEmpty($rule->getTriggerConfig());
    }

    public function testGetActionConfig(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $config = $rule->getActionConfig();
        $this->assertIsArray($config);
        $this->assertEquals('completed', $config['status']);
    }

    public function testGetActionConfigEmpty(): void
    {
        $this->setupRuleLookup(['action_config' => '']);
        $rule = new TaskAutomation(1);
        $this->assertEmpty($rule->getActionConfig());
    }

    public function testGetInfo(): void
    {
        $this->setupRuleLookup();
        $rule = new TaskAutomation(1);
        $this->assertIsArray($rule->getInfo());
    }

    public function testLookupFound(): void
    {
        $this->setupRuleLookup();
        $rule = TaskAutomation::lookup(1);
        $this->assertInstanceOf(TaskAutomation::class, $rule);
    }

    public function testLookupNotFound(): void
    {
        $rule = TaskAutomation::lookup(999);
        $this->assertNull($rule);
    }

    public function testGetTriggerLabels(): void
    {
        $labels = TaskAutomation::getTriggerLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('status_changed', $labels);
        $this->assertArrayHasKey('task_created', $labels);
        $this->assertArrayHasKey('deadline_passed', $labels);
        $this->assertCount(6, $labels);
    }

    public function testGetActionLabels(): void
    {
        $labels = TaskAutomation::getActionLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('change_status', $labels);
        $this->assertArrayHasKey('assign_to', $labels);
        $this->assertArrayHasKey('add_tag', $labels);
        $this->assertCount(6, $labels);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = TaskAutomation::create([
            'rule_name' => '',
            'board_id' => 1,
            'trigger_type' => 'status_changed',
            'action_type' => 'change_status',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('rule_name', $errors);
    }

    public function testCreateValidationNoBoardId(): void
    {
        $errors = [];
        $result = TaskAutomation::create([
            'rule_name' => 'Test',
            'board_id' => 0,
            'trigger_type' => 'status_changed',
            'action_type' => 'change_status',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('board_id', $errors);
    }

    public function testCreateValidationInvalidTrigger(): void
    {
        $errors = [];
        $result = TaskAutomation::create([
            'rule_name' => 'Test',
            'board_id' => 1,
            'trigger_type' => 'invalid',
            'action_type' => 'change_status',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('trigger_type', $errors);
    }

    public function testCreateValidationInvalidAction(): void
    {
        $errors = [];
        $result = TaskAutomation::create([
            'rule_name' => 'Test',
            'board_id' => 1,
            'trigger_type' => 'status_changed',
            'action_type' => 'invalid',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('action_type', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(10);
        $errors = [];
        $result = TaskAutomation::create([
            'rule_name' => 'Test Rule',
            'board_id' => 1,
            'trigger_type' => 'status_changed',
            'action_type' => 'change_status',
            'trigger_config' => [],
            'action_config' => [],
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateValidationNoId(): void
    {
        $errors = [];
        $result = TaskAutomation::update(0, [
            'rule_name' => 'Test',
            'trigger_type' => 'status_changed',
            'action_type' => 'change_status',
        ], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = TaskAutomation::update(1, [
            'rule_name' => 'Updated Rule',
            'trigger_type' => 'task_created',
            'action_type' => 'assign_to',
            'trigger_config' => [],
            'action_config' => [],
        ], $errors);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $this->assertTrue(TaskAutomation::delete(1));
    }

    public function testDeleteZeroId(): void
    {
        $this->assertFalse(TaskAutomation::delete(0));
    }

    public function testDeleteByBoardId(): void
    {
        $this->assertTrue(TaskAutomation::deleteByBoardId(1));
    }

    public function testToggleEnabled(): void
    {
        $this->assertTrue(TaskAutomation::toggleEnabled(1));
    }

    public function testToggleEnabledZeroId(): void
    {
        $this->assertFalse(TaskAutomation::toggleEnabled(0));
    }

    public function testCheckTriggerConditionsEmpty(): void
    {
        $this->assertTrue(TaskAutomation::checkTriggerConditions([], []));
    }

    public function testCheckTriggerConditionsFromStatusMatch(): void
    {
        $config = ['from_status' => 'open'];
        $context = ['from_status' => 'open'];
        $this->assertTrue(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsFromStatusMismatch(): void
    {
        $config = ['from_status' => 'open'];
        $context = ['from_status' => 'closed'];
        $this->assertFalse(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsToStatusMatch(): void
    {
        $config = ['to_status' => 'completed'];
        $context = ['to_status' => 'completed'];
        $this->assertTrue(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsToStatusMismatch(): void
    {
        $config = ['to_status' => 'completed'];
        $context = ['to_status' => 'open'];
        $this->assertFalse(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsFromPriorityMatch(): void
    {
        $config = ['from_priority' => 'low'];
        $context = ['from_priority' => 'low'];
        $this->assertTrue(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsToPriorityMismatch(): void
    {
        $config = ['to_priority' => 'urgent'];
        $context = ['to_priority' => 'low'];
        $this->assertFalse(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testCheckTriggerConditionsDaysBefore(): void
    {
        $config = ['days_before' => 3];
        $context = [];
        $this->assertTrue(TaskAutomation::checkTriggerConditions($config, $context));
    }

    public function testExecuteActionNoTaskId(): void
    {
        $this->assertFalse(TaskAutomation::executeAction(0, 'change_status', []));
    }

    public function testExecuteActionNoActionType(): void
    {
        $this->assertFalse(TaskAutomation::executeAction(1, '', []));
    }

    public function testExecuteActionUnknownType(): void
    {
        $this->assertFalse(TaskAutomation::executeAction(1, 'unknown_action', []));
    }

    public function testExecuteActionChangeStatus(): void
    {
        $result = TaskAutomation::executeAction(1, 'change_status', ['status' => 'completed']);
        $this->assertTrue($result);
    }

    public function testExecuteActionChangeStatusInvalid(): void
    {
        $result = TaskAutomation::executeAction(1, 'change_status', ['status' => 'invalid']);
        $this->assertTrue($result);
    }

    public function testExecuteActionChangePriority(): void
    {
        $result = TaskAutomation::executeAction(1, 'change_priority', ['priority' => 'high']);
        $this->assertTrue($result);
    }

    public function testExecuteActionAssignTo(): void
    {
        $result = TaskAutomation::executeAction(1, 'assign_to', ['staff_id' => 5]);
        $this->assertTrue($result);
    }

    public function testExecuteActionMoveToList(): void
    {
        DatabaseMock::setQueryResult('MAX(position)', [['MAX(position)' => 3]]);
        $result = TaskAutomation::executeAction(1, 'move_to_list', ['list_id' => 2]);
        $this->assertTrue($result);
    }

    public function testExecuteActionSendNotification(): void
    {
        $result = TaskAutomation::executeAction(1, 'send_notification', ['message' => 'Test']);
        $this->assertTrue($result);
    }

    public function testGetByBoardEmpty(): void
    {
        $this->assertEmpty(TaskAutomation::getByBoard(0));
    }

    public function testGetEnabledByBoardEmpty(): void
    {
        $this->assertEmpty(TaskAutomation::getEnabledByBoard(0));
    }
}
