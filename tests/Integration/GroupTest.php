<?php

use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Group')) {
            require_once INCLUDE_DIR . 'class.group.php';
        }

        DatabaseMock::reset();
    }

    private function makeGroupVars(array $overrides = []): array
    {
        return array_merge([
            'group_id' => 1,
            'group_name' => 'Support Team',
            'group_enabled' => 1,
            'depts' => [1, 2],
            'can_create_tickets' => 1,
            'can_delete_tickets' => 0,
            'can_edit_tickets' => 1,
            'can_transfer_tickets' => 1,
            'can_close_tickets' => 1,
            'can_ban_emails' => 0,
            'can_manage_kb' => 0,
        ], $overrides);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = Group::create(
            $this->makeGroupVars(['group_name' => '']),
            $errors
        );
        $this->assertFalse($result);
        $this->assertArrayHasKey('group_name', $errors);
    }

    public function testCreateValidationShortName(): void
    {
        $errors = [];
        $result = Group::create(
            $this->makeGroupVars(['group_name' => 'AB']),
            $errors
        );
        $this->assertFalse($result);
        $this->assertArrayHasKey('group_name', $errors);
    }

    public function testCreateValidationDuplicateName(): void
    {
        DatabaseMock::setQueryResult(GROUP_TABLE, [['group_id' => 1]]);
        $errors = [];
        $result = Group::create(
            $this->makeGroupVars(),
            $errors
        );
        $this->assertFalse($result);
        $this->assertArrayHasKey('group_name', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = Group::create(
            $this->makeGroupVars(),
            $errors
        );
        $this->assertNotFalse($result);
    }

    public function testUpdateValidationNoName(): void
    {
        $errors = [];
        $result = Group::update(1,
            $this->makeGroupVars(['group_name' => '']),
            $errors
        );
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        DatabaseMock::setAffectedRows(1);
        $errors = [];
        $result = Group::update(1,
            $this->makeGroupVars(),
            $errors
        );
        $this->assertTrue($result);
    }
}
