<?php

use PHPUnit\Framework\TestCase;

class InventoryCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('InventoryCategory')) {
            require_once INCLUDE_DIR . 'class.inventorycatalog.php';
        }

        DatabaseMock::reset();
    }

    public function testCategoryConstructorLoads(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_CATEGORIES_TABLE, [[
            'category_id' => 1,
            'category_name' => 'Laptops',
            'parent_id' => null,
            'description' => 'All laptops',
            'icon' => 'laptop',
            'sort_order' => 0,
            'is_active' => 1,
        ]]);
        $cat = new InventoryCategory(1);
        $this->assertEquals(1, $cat->getId());
    }

    public function testCategoryConstructorNotFound(): void
    {
        $cat = new InventoryCategory(999);
        $this->assertEquals(0, $cat->getId());
    }

    public function testCategoryGetName(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_CATEGORIES_TABLE, [[
            'category_id' => 1,
            'category_name' => 'Monitors',
            'parent_id' => null,
            'description' => '',
            'icon' => 'desktop',
            'sort_order' => 0,
            'is_active' => 1,
        ]]);
        $cat = new InventoryCategory(1);
        $this->assertEquals('Monitors', $cat->getName());
    }

    public function testCategoryIsActive(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_CATEGORIES_TABLE, [[
            'category_id' => 1,
            'category_name' => 'Test',
            'parent_id' => null,
            'description' => '',
            'icon' => 'desktop',
            'sort_order' => 0,
            'is_active' => 1,
        ]]);
        $cat = new InventoryCategory(1);
        $this->assertTrue($cat->isActive());
    }

    public function testCategoryLookupFound(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_CATEGORIES_TABLE, [[
            'category_id' => 1,
            'category_name' => 'Test',
            'parent_id' => null,
            'description' => '',
            'icon' => 'desktop',
            'sort_order' => 0,
            'is_active' => 1,
        ]]);
        $cat = InventoryCategory::lookup(1);
        $this->assertInstanceOf(InventoryCategory::class, $cat);
    }

    public function testCategoryLookupNotFound(): void
    {
        $cat = InventoryCategory::lookup(999);
        $this->assertNull($cat);
    }

    public function testCategoryCreateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryCategory::create([
            'category_name' => '',
            'parent_id' => 0,
            'description' => '',
            'icon' => '',
            'sort_order' => 0,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('category_name', $errors);
    }

    public function testCategoryCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = InventoryCategory::create([
            'category_name' => 'Printers',
            'parent_id' => 0,
            'description' => 'All printers',
            'icon' => 'print',
            'sort_order' => 1,
        ], $errors);
        $this->assertNotEquals(0, $result);
    }

    public function testCategoryUpdateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryCategory::update(1, [
            'category_name' => '',
            'parent_id' => 0,
            'description' => '',
            'icon' => '',
            'sort_order' => 0,
            'is_active' => 1,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCategoryUpdateSuccessful(): void
    {
        $errors = [];
        $result = InventoryCategory::update(1, [
            'category_name' => 'Updated',
            'parent_id' => 0,
            'description' => 'Updated description',
            'icon' => 'desktop',
            'sort_order' => 0,
            'is_active' => 1,
        ], $errors);
        $this->assertTrue($result);
    }

    public function testBrandConstructorLoads(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_BRANDS_TABLE, [[
            'brand_id' => 1,
            'brand_name' => 'HP',
            'is_active' => 1,
        ]]);
        $brand = new InventoryBrand(1);
        $this->assertEquals(1, $brand->getId());
    }

    public function testBrandGetName(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_BRANDS_TABLE, [[
            'brand_id' => 1,
            'brand_name' => 'Lenovo',
            'is_active' => 1,
        ]]);
        $brand = new InventoryBrand(1);
        $this->assertEquals('Lenovo', $brand->getName());
    }

    public function testBrandIsActive(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_BRANDS_TABLE, [[
            'brand_id' => 1,
            'brand_name' => 'Test',
            'is_active' => 0,
        ]]);
        $brand = new InventoryBrand(1);
        $this->assertFalse($brand->isActive());
    }

    public function testBrandLookupFound(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_BRANDS_TABLE, [[
            'brand_id' => 1,
            'brand_name' => 'Dell',
            'is_active' => 1,
        ]]);
        $brand = InventoryBrand::lookup(1);
        $this->assertInstanceOf(InventoryBrand::class, $brand);
    }

    public function testBrandLookupNotFound(): void
    {
        $brand = InventoryBrand::lookup(999);
        $this->assertNull($brand);
    }

    public function testBrandCreateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryBrand::create(['brand_name' => ''], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('brand_name', $errors);
    }

    public function testBrandCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(3);
        $errors = [];
        $result = InventoryBrand::create(['brand_name' => 'Apple'], $errors);
        $this->assertNotEquals(0, $result);
    }

    public function testBrandUpdateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryBrand::update(1, ['brand_name' => '', 'is_active' => 1], $errors);
        $this->assertFalse($result);
    }

    public function testBrandUpdateSuccessful(): void
    {
        $errors = [];
        $result = InventoryBrand::update(1, ['brand_name' => 'Updated', 'is_active' => 1], $errors);
        $this->assertTrue($result);
    }

    public function testModelConstructorLoads(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_MODELS_TABLE, [[
            'model_id' => 1,
            'model_name' => 'ThinkPad X1',
            'brand_id' => 1,
            'brand_name' => 'Lenovo',
            'category_id' => 1,
            'is_active' => 1,
        ]]);
        $model = new InventoryModel(1);
        $this->assertEquals(1, $model->getId());
    }

    public function testModelGetName(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_MODELS_TABLE, [[
            'model_id' => 1,
            'model_name' => 'MacBook Pro',
            'brand_id' => 1,
            'brand_name' => 'Apple',
            'category_id' => 1,
            'is_active' => 1,
        ]]);
        $model = new InventoryModel(1);
        $this->assertEquals('MacBook Pro', $model->getName());
    }

    public function testModelGetBrandName(): void
    {
        DatabaseMock::setQueryResult(INVENTORY_MODELS_TABLE, [[
            'model_id' => 1,
            'model_name' => 'Test',
            'brand_id' => 1,
            'brand_name' => 'TestBrand',
            'category_id' => 1,
            'is_active' => 1,
        ]]);
        $model = new InventoryModel(1);
        $this->assertEquals('TestBrand', $model->getBrandName());
    }

    public function testModelLookupNotFound(): void
    {
        $model = InventoryModel::lookup(999);
        $this->assertNull($model);
    }

    public function testModelCreateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryModel::create([
            'model_name' => '',
            'brand_id' => 1,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('model_name', $errors);
    }

    public function testModelCreateValidationNoBrand(): void
    {
        $errors = [];
        $result = InventoryModel::create([
            'model_name' => 'Test',
            'brand_id' => 0,
        ], $errors);
        $this->assertEquals(0, $result);
        $this->assertArrayHasKey('brand_id', $errors);
    }

    public function testModelCreateSuccessful(): void
    {
        DatabaseMock::setLastInsertId(3);
        $errors = [];
        $result = InventoryModel::create([
            'model_name' => 'Latitude',
            'brand_id' => 1,
            'category_id' => 1,
        ], $errors);
        $this->assertNotEquals(0, $result);
    }

    public function testModelUpdateValidationNoName(): void
    {
        $errors = [];
        $result = InventoryModel::update(1, [
            'model_name' => '',
            'brand_id' => 1,
            'category_id' => 1,
            'is_active' => 1,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testModelUpdateSuccessful(): void
    {
        $errors = [];
        $result = InventoryModel::update(1, [
            'model_name' => 'Updated',
            'brand_id' => 1,
            'category_id' => 1,
            'is_active' => 1,
        ], $errors);
        $this->assertTrue($result);
    }
}
