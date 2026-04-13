<?php

use PHPUnit\Framework\TestCase;

class InventoryLocationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('InventoryLocation')) {
            require_once INCLUDE_DIR . 'class.inventorylocation.php';
        }

        DatabaseMock::reset();
    }

    private function makeLocationRow(array $overrides = []): array
    {
        return array_merge([
            'location_id' => 1,
            'location_name' => 'Office 101',
            'parent_id' => null,
            'location_type' => 'room',
            'description' => 'Main office',
            'sort_order' => 0,
            'is_active' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupLocationLookup(array $overrides = []): void
    {
        $row = $this->makeLocationRow($overrides);
        DatabaseMock::setQueryResult(LOCATIONS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupLocationLookup();
        $loc = new InventoryLocation(1);
        $this->assertEquals(1, $loc->getId());
    }

    public function testConstructorNotFound(): void
    {
        $loc = new InventoryLocation(999);
        $this->assertEquals(0, $loc->getId());
    }

    public function testGetName(): void
    {
        $this->setupLocationLookup();
        $loc = new InventoryLocation(1);
        $this->assertEquals('Office 101', $loc->getName());
    }

    public function testGetLocationType(): void
    {
        $this->setupLocationLookup(['location_type' => 'building']);
        $loc = new InventoryLocation(1);
        $this->assertEquals('building', $loc->getLocationType());
    }

    public function testGetDescription(): void
    {
        $this->setupLocationLookup();
        $loc = new InventoryLocation(1);
        $this->assertEquals('Main office', $loc->getDescription());
    }

    public function testIsActive(): void
    {
        $this->setupLocationLookup(['is_active' => 1]);
        $loc = new InventoryLocation(1);
        $this->assertTrue($loc->isActive());
    }

    public function testIsNotActive(): void
    {
        $this->setupLocationLookup(['is_active' => 0]);
        $loc = new InventoryLocation(1);
        $this->assertFalse($loc->isActive());
    }

    public function testLookupFound(): void
    {
        $this->setupLocationLookup();
        $loc = InventoryLocation::lookup(1);
        $this->assertInstanceOf(InventoryLocation::class, $loc);
    }

    public function testLookupNotFound(): void
    {
        $loc = InventoryLocation::lookup(999);
        $this->assertNull($loc);
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryLocation::create([
            'location_name' => '',
            'parent_id' => 0,
            'location_type' => 'room',
            'description' => '',
            'sort_order' => 0,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('location_name', $errors);
    }

    public function testCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = InventoryLocation::create([
            'location_name' => 'New Room',
            'parent_id' => 0,
            'location_type' => 'room',
            'description' => 'New room description',
            'sort_order' => 1,
        ], $errors);
        $this->assertNotEquals(0, $result);
    }

    public function testUpdateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryLocation::update(1, [
            'location_name' => '',
            'parent_id' => 0,
            'location_type' => 'room',
            'description' => '',
            'sort_order' => 0,
            'is_active' => 1,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testUpdateSuccessful(): void
    {
        $errors = [];
        $result = InventoryLocation::update(1, [
            'location_name' => 'Updated Room',
            'parent_id' => 0,
            'location_type' => 'room',
            'description' => 'Updated',
            'sort_order' => 0,
            'is_active' => 1,
        ], $errors);
        $this->assertTrue($result);
    }

    public function testGetTypeLabel(): void
    {
        $this->assertEquals('Здание', InventoryLocation::getTypeLabel('building'));
        $this->assertEquals('Этаж', InventoryLocation::getTypeLabel('floor'));
        $this->assertEquals('Кабинет', InventoryLocation::getTypeLabel('room'));
        $this->assertEquals('Склад', InventoryLocation::getTypeLabel('storage'));
        $this->assertEquals('Стеллаж', InventoryLocation::getTypeLabel('rack'));
        $this->assertEquals('Другое', InventoryLocation::getTypeLabel('other'));
    }

    public function testGetTypeLabelUnknown(): void
    {
        $this->assertEquals('custom', InventoryLocation::getTypeLabel('custom'));
    }

    public function testGetTypeIcon(): void
    {
        $this->assertEquals('building', InventoryLocation::getTypeIcon('building'));
        $this->assertEquals('bars', InventoryLocation::getTypeIcon('floor'));
        $this->assertEquals('home', InventoryLocation::getTypeIcon('room'));
        $this->assertEquals('archive', InventoryLocation::getTypeIcon('storage'));
        $this->assertEquals('th', InventoryLocation::getTypeIcon('rack'));
    }

    public function testGetTypeIconUnknown(): void
    {
        $this->assertEquals('map-marker', InventoryLocation::getTypeIcon('custom'));
    }

    public function testGetTypes(): void
    {
        $types = InventoryLocation::getTypes();
        $this->assertIsArray($types);
        $this->assertCount(6, $types);
        $this->assertArrayHasKey('building', $types);
        $this->assertArrayHasKey('room', $types);
    }
}
