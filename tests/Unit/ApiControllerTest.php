<?php

use PHPUnit\Framework\TestCase;

class ApiControllerTest extends TestCase
{
    private ApiController $controller;

    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_GET = [];
        $_POST = [];

        if (!class_exists('ApiController')) {
            require_once INCLUDE_DIR . 'class.apicontroller.php';
        }

        $this->controller = new ApiController();
    }

    public function testConstructorSetsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller = new ApiController();
        $this->assertEquals('POST', $controller->request_method);
    }

    public function testGetInputReturnsValue(): void
    {
        $this->controller->request_data = ['name' => 'John'];
        $this->assertEquals('John', $this->controller->getInput('name'));
    }

    public function testGetInputReturnsDefault(): void
    {
        $this->controller->request_data = [];
        $this->assertEquals('default', $this->controller->getInput('missing', 'default'));
    }

    public function testGetInputReturnsNullByDefault(): void
    {
        $this->controller->request_data = [];
        $this->assertNull($this->controller->getInput('missing'));
    }

    public function testGetQueryReturnsValue(): void
    {
        $this->controller->query_params = ['page' => '2'];
        $this->assertEquals('2', $this->controller->getQuery('page'));
    }

    public function testGetQueryReturnsDefault(): void
    {
        $this->controller->query_params = [];
        $this->assertEquals(1, $this->controller->getQuery('page', 1));
    }

    public function testGetPathParamReturnsValue(): void
    {
        $this->controller->path_params = ['id' => '42'];
        $this->assertEquals('42', $this->controller->getPathParam('id'));
    }

    public function testSetPathParams(): void
    {
        $this->controller->setPathParams(['id' => '42', 'action' => 'view']);
        $this->assertEquals('42', $this->controller->getPathParam('id'));
        $this->assertEquals('view', $this->controller->getPathParam('action'));
    }

    public function testValidateRequiredSuccess(): void
    {
        $this->controller->request_data = ['name' => 'John', 'email' => 'test@test.com'];
        $errors = [];
        $this->assertTrue($this->controller->validateRequired(['name', 'email'], $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateRequiredFailure(): void
    {
        $this->controller->request_data = ['name' => 'John'];
        $errors = [];
        $this->assertFalse($this->controller->validateRequired(['name', 'email'], $errors));
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateRequiredEmptyString(): void
    {
        $this->controller->request_data = ['name' => ''];
        $errors = [];
        $this->assertFalse($this->controller->validateRequired(['name'], $errors));
    }

    public function testValidateEmailValid(): void
    {
        $errors = [];
        $this->assertTrue($this->controller->validateEmail('user@example.com', $errors));
    }

    public function testValidateEmailInvalid(): void
    {
        $errors = [];
        $this->assertFalse($this->controller->validateEmail('not-email', $errors));
        $this->assertArrayHasKey('email', $errors);
    }

    public function testSanitizeInt(): void
    {
        $this->assertSame(42, $this->controller->sanitize('42', 'int'));
        $this->assertSame(0, $this->controller->sanitize('abc', 'int'));
    }

    public function testSanitizeFloat(): void
    {
        $this->assertSame(3.14, $this->controller->sanitize('3.14', 'float'));
    }

    public function testSanitizeBool(): void
    {
        $this->assertTrue($this->controller->sanitize('1', 'bool'));
        $this->assertFalse($this->controller->sanitize('', 'bool'));
    }

    public function testSanitizeString(): void
    {
        $result = $this->controller->sanitize('<script>alert(1)</script>', 'string');
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSanitizeEmail(): void
    {
        $result = $this->controller->sanitize('user@example.com', 'email');
        $this->assertEquals('user@example.com', $result);
    }

    public function testGetSortParamsDefaults(): void
    {
        $this->controller->query_params = [];
        $params = $this->controller->getSortParams();
        $this->assertEquals('created', $params['sort']);
        $this->assertEquals('DESC', $params['order']);
    }

    public function testGetSortParamsFromQuery(): void
    {
        $this->controller->query_params = ['sort' => 'name', 'order' => 'asc'];
        $params = $this->controller->getSortParams(['name', 'created']);
        $this->assertEquals('name', $params['sort']);
        $this->assertEquals('ASC', $params['order']);
    }

    public function testGetSortParamsInvalidFieldFallsBackToDefault(): void
    {
        $this->controller->query_params = ['sort' => 'invalid'];
        $params = $this->controller->getSortParams(['name', 'created']);
        $this->assertEquals('created', $params['sort']);
    }

    public function testGetSortParamsInvalidOrderFallsBackToDefault(): void
    {
        $this->controller->query_params = ['order' => 'INVALID'];
        $params = $this->controller->getSortParams();
        $this->assertEquals('DESC', $params['order']);
    }

    public function testBuildOrderClause(): void
    {
        $this->controller->query_params = ['sort' => 'name', 'order' => 'asc'];
        $clause = $this->controller->buildOrderClause(['name', 'created']);
        $this->assertEquals(' ORDER BY name ASC', $clause);
    }

    public function testFormatDateValid(): void
    {
        $result = $this->controller->formatDate('2026-03-15 14:30:00');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $result);
    }

    public function testFormatDateNull(): void
    {
        $this->assertNull($this->controller->formatDate(null));
        $this->assertNull($this->controller->formatDate(''));
        $this->assertNull($this->controller->formatDate('0000-00-00 00:00:00'));
    }

    public function testFormatBool(): void
    {
        $this->assertTrue($this->controller->formatBool(1));
        $this->assertTrue($this->controller->formatBool('1'));
        $this->assertFalse($this->controller->formatBool(0));
        $this->assertFalse($this->controller->formatBool(''));
    }

    public function testBuildSearchClause(): void
    {
        $result = $this->controller->buildSearchClause('test', ['name', 'email']);
        $this->assertStringContainsString('LIKE', $result);
        $this->assertStringContainsString('name', $result);
        $this->assertStringContainsString('email', $result);
        $this->assertStringContainsString('OR', $result);
    }

    public function testBuildSearchClauseWithPrefix(): void
    {
        $result = $this->controller->buildSearchClause('test', ['name'], 'ticket');
        $this->assertStringContainsString('ticket.name', $result);
    }

    public function testBuildSearchClauseEmpty(): void
    {
        $this->assertEquals('', $this->controller->buildSearchClause('', ['name']));
        $this->assertEquals('', $this->controller->buildSearchClause('test', []));
    }

    public function testGetResponseTime(): void
    {
        usleep(10000);
        $time = $this->controller->getResponseTime();
        $this->assertGreaterThan(0, $time);
    }

    public function testSetToken(): void
    {
        $mockToken = new stdClass();
        $mockToken->id = 1;
        $this->controller->setToken($mockToken);
        $this->assertSame($mockToken, $this->controller->token);
    }

    public function testSuccessHelper(): void
    {
        $response = new ApiResponse();
        $result = $response->success(['key' => 'val']);
        $this->assertInstanceOf(ApiResponse::class, $result);
    }

    public function testPaginatedHelper(): void
    {
        $response = new ApiResponse();
        $data = [['id' => 1], ['id' => 2]];
        $result = $response->paginated($data, 50, 1, 10);
        $this->assertInstanceOf(ApiResponse::class, $result);
    }

    public function testParseRequestDataGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['status' => 'open'];
        $controller = new ApiController();
        $this->assertEquals('open', $controller->getInput('status'));
    }

    public function testBuildLimitClause(): void
    {
        $cfg = new class {
            public function get($key, $default = null) { return $default; }
        };
        $GLOBALS['cfg'] = $cfg;

        $this->controller->query_params = ['page' => '2', 'per_page' => '10'];
        $clause = $this->controller->buildLimitClause();
        $this->assertEquals(' LIMIT 10, 10', $clause);
    }
}
