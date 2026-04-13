<?php

use PHPUnit\Framework\TestCase;

class InventoryItemTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('InventoryItem')) {
            require_once INCLUDE_DIR . 'class.inventory.php';
        }

        DatabaseMock::reset();
    }

    private function makeItemRow(array $overrides = []): array
    {
        return array_merge([
            'item_id' => 1,
            'inventory_number' => 'INV-001',
            'serial_number' => 'SN123456',
            'part_number' => 'PN789',
            'category_id' => 1,
            'category_name' => 'Laptops',
            'brand_id' => 1,
            'brand_name' => 'Dell',
            'model_id' => 1,
            'model_name' => 'Latitude 5520',
            'custom_model' => '',
            'location_id' => 1,
            'location_name' => 'Office 101',
            'assigned_staff_id' => 1,
            'staff_name' => 'John Doe',
            'created_by' => 1,
            'created_by_name' => 'Admin',
            'status' => 'active',
            'assignment_type' => 'workplace',
            'purchase_date' => '2025-06-01',
            'warranty_until' => '2028-06-01',
            'cost' => '1200.00',
            'description' => 'Office laptop',
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupItemLookup(array $overrides = []): void
    {
        $row = $this->makeItemRow($overrides);
        DatabaseMock::setQueryResult(INVENTORY_ITEMS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals(1, $item->getId());
    }

    public function testConstructorNotFound(): void
    {
        $item = new InventoryItem(999);
        $this->assertEquals(0, $item->getId());
    }

    public function testGetInventoryNumber(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('INV-001', $item->getInventoryNumber());
    }

    public function testGetSerialNumber(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('SN123456', $item->getSerialNumber());
    }

    public function testGetCategoryName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('Laptops', $item->getCategoryName());
    }

    public function testGetBrandName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('Dell', $item->getBrandName());
    }

    public function testGetModelName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('Latitude 5520', $item->getModelName());
    }

    public function testGetModelDisplayWithModelName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('Latitude 5520', $item->getModelDisplay());
    }

    public function testGetModelDisplayWithCustomModel(): void
    {
        $this->setupItemLookup(['model_name' => '', 'custom_model' => 'Custom PC']);
        $item = new InventoryItem(1);
        $this->assertEquals('Custom PC', $item->getModelDisplay());
    }

    public function testGetModelDisplayEmpty(): void
    {
        $this->setupItemLookup(['model_name' => '', 'custom_model' => '']);
        $item = new InventoryItem(1);
        $this->assertEquals('', $item->getModelDisplay());
    }

    public function testGetLocationName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('Office 101', $item->getLocationName());
    }

    public function testGetAssignedStaffName(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('John Doe', $item->getAssignedStaffName());
    }

    public function testGetStatus(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('active', $item->getStatus());
    }

    public function testGetCost(): void
    {
        $this->setupItemLookup();
        $item = new InventoryItem(1);
        $this->assertEquals('1200.00', $item->getCost());
    }

    public function testGetStatusLabels(): void
    {
        $labels = InventoryItem::getStatusLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('active', $labels);
        $this->assertArrayHasKey('in_repair', $labels);
        $this->assertArrayHasKey('decommissioned', $labels);
        $this->assertCount(5, $labels);
    }

    public function testGetStatusLabel(): void
    {
        $this->setupItemLookup(['status' => 'active']);
        $item = new InventoryItem(1);
        $this->assertEquals('Активно', $item->getStatusLabel());
    }

    public function testGetStatusLabelInRepair(): void
    {
        $this->setupItemLookup(['status' => 'in_repair']);
        $item = new InventoryItem(1);
        $this->assertEquals('В ремонте', $item->getStatusLabel());
    }

    public function testGetAssignmentLabels(): void
    {
        $labels = InventoryItem::getAssignmentLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('workplace', $labels);
        $this->assertArrayHasKey('remote', $labels);
        $this->assertArrayHasKey('storage', $labels);
        $this->assertCount(5, $labels);
    }

    public function testGetAssignmentLabel(): void
    {
        $this->setupItemLookup(['assignment_type' => 'workplace']);
        $item = new InventoryItem(1);
        $this->assertEquals('Рабочее место', $item->getAssignmentLabel());
    }

    public function testGetActionLabels(): void
    {
        $labels = InventoryItem::getActionLabels();
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('created', $labels);
        $this->assertArrayHasKey('moved', $labels);
        $this->assertArrayHasKey('assigned', $labels);
        $this->assertCount(6, $labels);
    }

    public function testLookupFound(): void
    {
        $this->setupItemLookup();
        $item = InventoryItem::lookup(1);
        $this->assertInstanceOf(InventoryItem::class, $item);
    }

    public function testLookupNotFound(): void
    {
        $item = InventoryItem::lookup(999);
        $this->assertNull($item);
    }

    public function testCreateValidationNoCategory(): void
    {
        $errors = [];
        $result = InventoryItem::create([
            'category_id' => 0,
            'inventory_number' => '',
            'description' => '',
            'created_by' => 1,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('category_id', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = InventoryItem::create([
            'category_id' => 1,
            'inventory_number' => '',
            'serial_number' => '',
            'part_number' => '',
            'brand_id' => 0,
            'model_id' => 0,
            'custom_model' => '',
            'location_id' => 0,
            'assigned_staff_id' => 0,
            'assignment_type' => 'workplace',
            'status' => 'active',
            'purchase_date' => '',
            'warranty_until' => '',
            'cost' => '',
            'description' => 'Test item',
            'created_by' => 1,
        ], $errors);
        $this->assertNotEquals(0, $result);
    }

    public function testLogHistory(): void
    {
        $result = InventoryItem::logHistory(1, 'created', '', 'Создано', 1);
        $this->assertNotFalse($result);
    }

    public function testDelete(): void
    {
        $result = InventoryItem::delete(1);
        $this->assertNotFalse($result);
    }
}
