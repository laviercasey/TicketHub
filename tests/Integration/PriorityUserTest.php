<?php

use PHPUnit\Framework\TestCase;

class PriorityUserTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('PriorityUser')) {
            require_once INCLUDE_DIR . 'class.priorityuser.php';
        }

        DatabaseMock::reset();
    }

    private function makePriorityUserRow(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'email' => 'vip@example.com',
            'description' => 'VIP Customer',
            'is_active' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupPriorityUserLookup(array $overrides = []): void
    {
        $row = $this->makePriorityUserRow($overrides);
        DatabaseMock::setQueryResult(PRIORITY_USERS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupPriorityUserLookup();
        $pu = new PriorityUser(1);
        $this->assertEquals(1, $pu->getId());
    }

    public function testConstructorNotFound(): void
    {
        $pu = new PriorityUser(999);
        $this->assertEquals(0, $pu->getId());
    }

    public function testGetEmail(): void
    {
        $this->setupPriorityUserLookup();
        $pu = new PriorityUser(1);
        $this->assertEquals('vip@example.com', $pu->getEmail());
    }

    public function testGetDescription(): void
    {
        $this->setupPriorityUserLookup();
        $pu = new PriorityUser(1);
        $this->assertEquals('VIP Customer', $pu->getDescription());
    }

    public function testIsActive(): void
    {
        $this->setupPriorityUserLookup(['is_active' => 1]);
        $pu = new PriorityUser(1);
        $this->assertTrue($pu->isActive());
    }

    public function testIsNotActive(): void
    {
        $this->setupPriorityUserLookup(['is_active' => 0]);
        $pu = new PriorityUser(1);
        $this->assertFalse($pu->isActive());
    }

    public function testGetCreated(): void
    {
        $this->setupPriorityUserLookup();
        $pu = new PriorityUser(1);
        $this->assertEquals('2026-01-01 00:00:00', $pu->getCreated());
    }

    public function testLookupFound(): void
    {
        $this->setupPriorityUserLookup();
        $pu = PriorityUser::lookup(1);
        $this->assertInstanceOf(PriorityUser::class, $pu);
    }

    public function testLookupNotFound(): void
    {
        $pu = PriorityUser::lookup(999);
        $this->assertNull($pu);
    }

    public function testLookupZero(): void
    {
        $pu = PriorityUser::lookup(0);
        $this->assertNull($pu);
    }

    public function testCreateValidationNoEmail(): void
    {
        $errors = [];
        $result = PriorityUser::create([
            'email' => '',
            'description' => 'Test',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testCreateValidationInvalidEmail(): void
    {
        $errors = [];
        $result = PriorityUser::create([
            'email' => 'not-an-email',
            'description' => 'Test',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = PriorityUser::create([
            'email' => 'new@example.com',
            'description' => 'New VIP',
            'is_active' => true,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testUpdateValidationNoEmail(): void
    {
        $this->setupPriorityUserLookup();
        $pu = new PriorityUser(1);
        $errors = [];
        $result = $pu->update([
            'email' => '',
            'description' => 'Test',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testIsPriorityEmailEmpty(): void
    {
        $this->assertFalse(PriorityUser::isPriorityEmail(''));
    }

    public function testIsPriorityEmailFound(): void
    {
        DatabaseMock::setQueryResult(PRIORITY_USERS_TABLE, [['id' => 1]]);
        $this->assertTrue(PriorityUser::isPriorityEmail('vip@example.com'));
    }

    public function testIsPriorityEmailNotFound(): void
    {
        $this->assertFalse(PriorityUser::isPriorityEmail('nobody@example.com'));
    }
}
