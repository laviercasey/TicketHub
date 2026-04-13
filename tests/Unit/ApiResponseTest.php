<?php

use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $response = new ApiResponse();

        $this->assertNull($response->data);
        $this->assertNull($response->error);
        $this->assertEquals(200, $response->status_code);
        $this->assertIsArray($response->meta);
        $this->assertEquals('1.0', $response->meta['version']);
        $this->assertArrayHasKey('timestamp', $response->meta);
        $this->assertArrayHasKey('request_id', $response->meta);
    }

    public function testSetData(): void
    {
        $response = new ApiResponse();
        $result = $response->setData(['key' => 'value']);

        $this->assertSame($response, $result);
        $this->assertEquals(['key' => 'value'], $response->data);
    }

    public function testSetError(): void
    {
        $response = new ApiResponse();
        $result = $response->setError('ERR_CODE', 'Error message');

        $this->assertSame($response, $result);
        $this->assertEquals('ERR_CODE', $response->error['code']);
        $this->assertEquals('Error message', $response->error['message']);
    }

    public function testSetErrorWithDetails(): void
    {
        $response = new ApiResponse();
        $response->setError('ERR', 'Message', ['field' => 'invalid']);

        $this->assertEquals(['field' => 'invalid'], $response->error['details']);
    }

    public function testSetStatusCode(): void
    {
        $response = new ApiResponse();
        $result = $response->setStatusCode(404);

        $this->assertSame($response, $result);
        $this->assertEquals(404, $response->status_code);
    }

    public function testAddHeader(): void
    {
        $response = new ApiResponse();
        $result = $response->addHeader('X-Custom', 'value');

        $this->assertSame($response, $result);
        $this->assertEquals('value', $response->headers['X-Custom']);
    }

    public function testSetPagination(): void
    {
        $response = new ApiResponse();
        $result = $response->setPagination(100, 20, 20, 1);

        $this->assertSame($response, $result);
        $this->assertEquals(100, $response->meta['pagination']['total']);
        $this->assertEquals(20, $response->meta['pagination']['count']);
        $this->assertEquals(20, $response->meta['pagination']['per_page']);
        $this->assertEquals(1, $response->meta['pagination']['current_page']);
        $this->assertEquals(5, $response->meta['pagination']['total_pages']);
    }

    public function testSetPaginationLinks(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/tickets';
        $response = new ApiResponse();
        $response->setPagination(100, 20, 20, 2);

        $this->assertNotNull($response->meta['pagination']['links']['next']);
        $this->assertNotNull($response->meta['pagination']['links']['prev']);
    }

    public function testSetPaginationFirstPage(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/tickets';
        $response = new ApiResponse();
        $response->setPagination(100, 20, 20, 1);

        $this->assertNotNull($response->meta['pagination']['links']['next']);
        $this->assertNull($response->meta['pagination']['links']['prev']);
    }

    public function testSetPaginationLastPage(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/tickets';
        $response = new ApiResponse();
        $response->setPagination(100, 20, 20, 5);

        $this->assertNull($response->meta['pagination']['links']['next']);
        $this->assertNotNull($response->meta['pagination']['links']['prev']);
    }

    public function testSetRateLimitHeaders(): void
    {
        $response = new ApiResponse();
        $result = $response->setRateLimitHeaders(1000, 999, time() + 3600, 3600);

        $this->assertSame($response, $result);
        $this->assertEquals(1000, $response->headers['X-RateLimit-Limit']);
        $this->assertEquals(999, $response->headers['X-RateLimit-Remaining']);
    }

    public function testGenerateRequestIdFormat(): void
    {
        $response = new ApiResponse();
        $id = $response->generateRequestId();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id
        );
    }

    public function testSuccessFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->success(['id' => 1], 'Created');

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertEquals(200, $result->status_code);
        $this->assertEquals(['id' => 1], $result->data);
        $this->assertEquals('Created', $result->meta['message']);
    }

    public function testCreatedFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->created(['id' => 1]);

        $this->assertEquals(201, $result->status_code);
        $this->assertEquals(['id' => 1], $result->data);
    }

    public function testBadRequestFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->badRequest('Invalid input');

        $this->assertEquals(400, $result->status_code);
        $this->assertEquals('BAD_REQUEST', $result->error['code']);
    }

    public function testValidationErrorFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->validationError(['name' => 'required']);

        $this->assertEquals(400, $result->status_code);
        $this->assertEquals('VALIDATION_ERROR', $result->error['code']);
        $this->assertEquals(['name' => 'required'], $result->error['details']);
    }

    public function testUnauthorizedFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->unauthorized();

        $this->assertEquals(401, $result->status_code);
        $this->assertEquals('UNAUTHORIZED', $result->error['code']);
    }

    public function testInvalidTokenFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->invalidToken();

        $this->assertEquals(401, $result->status_code);
        $this->assertEquals('INVALID_TOKEN', $result->error['code']);
    }

    public function testForbiddenFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->forbidden();

        $this->assertEquals(403, $result->status_code);
        $this->assertEquals('FORBIDDEN', $result->error['code']);
    }

    public function testInsufficientPermissionsFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->insufficientPermissions();

        $this->assertEquals(403, $result->status_code);
        $this->assertEquals('INSUFFICIENT_PERMISSIONS', $result->error['code']);
    }

    public function testNotFoundFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->notFound();

        $this->assertEquals(404, $result->status_code);
        $this->assertEquals('RESOURCE_NOT_FOUND', $result->error['code']);
    }

    public function testMethodNotAllowedFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->methodNotAllowed(['GET', 'POST']);

        $this->assertEquals(405, $result->status_code);
        $this->assertEquals('METHOD_NOT_ALLOWED', $result->error['code']);
        $this->assertEquals('GET, POST', $result->headers['Allow']);
    }

    public function testConflictFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->conflict();

        $this->assertEquals(409, $result->status_code);
        $this->assertEquals('DUPLICATE_RESOURCE', $result->error['code']);
    }

    public function testUnprocessableEntityFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->unprocessableEntity('Cannot process');

        $this->assertEquals(422, $result->status_code);
        $this->assertEquals('UNPROCESSABLE_ENTITY', $result->error['code']);
    }

    public function testRateLimitExceededFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->rateLimitExceeded(60);

        $this->assertEquals(429, $result->status_code);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $result->error['code']);
        $this->assertEquals(60, $result->error['retry_after']);
        $this->assertEquals(60, $result->headers['Retry-After']);
    }

    public function testInternalErrorFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->internalError();

        $this->assertEquals(500, $result->status_code);
        $this->assertEquals('INTERNAL_ERROR', $result->error['code']);
    }

    public function testServiceUnavailableFactory(): void
    {
        $response = new ApiResponse();
        $result = $response->serviceUnavailable();

        $this->assertEquals(503, $result->status_code);
        $this->assertEquals('SERVICE_UNAVAILABLE', $result->error['code']);
    }

    public function testPaginatedFactory(): void
    {
        $response = new ApiResponse();
        $data = [['id' => 1], ['id' => 2]];
        $result = $response->paginated($data, 50, 1, 20);

        $this->assertEquals($data, $result->data);
        $this->assertEquals(50, $result->meta['pagination']['total']);
        $this->assertEquals(2, $result->meta['pagination']['count']);
    }

    public function testApiResponseHelperFunction(): void
    {
        $response = api_response();
        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testBuildPaginationUrl(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/tickets?status=open';
        $response = new ApiResponse();
        $url = $response->buildPaginationUrl(3);

        $this->assertStringContainsString('page=3', $url);
    }
}
