<?php

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Api')) {
            require_once INCLUDE_DIR . 'class.api.php';
        }

        DatabaseMock::reset();
    }

    public function testLogRequestBasic(): void
    {
        $result = Api::logRequest(1, '/api/v1/tickets', 'GET', null, 200, 150, '127.0.0.1', 'TestAgent');
        $this->assertInstanceOf(MockQueryResult::class, $result);
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString(API_LOG_TABLE, $lastQuery);
        $this->assertStringContainsString('/api/v1/tickets', $lastQuery);
    }

    public function testLogRequestWithData(): void
    {
        $data = ['subject' => 'Test ticket', 'message' => 'Hello'];
        Api::logRequest(1, '/api/v1/tickets', 'POST', $data, 201, 200);
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('Test ticket', $lastQuery);
    }

    public function testLogRequestWithLargeData(): void
    {
        $data = ['large' => str_repeat('x', 70000)];
        Api::logRequest(1, '/api/v1/tickets', 'POST', $data, 201);
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('truncated', $lastQuery);
    }

    public function testLogRequestNullData(): void
    {
        Api::logRequest(1, '/api/v1/tickets', 'GET', null, 200);
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('NULL', $lastQuery);
    }

    public function testGetStatsReturnsStructure(): void
    {
        DatabaseMock::setQueryResult(API_LOG_TABLE, [['total' => '42']]);
        $stats = Api::getStats(null, 7);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('top_endpoints', $stats);
        $this->assertArrayHasKey('avg_response_time', $stats);
    }

    public function testGetStatsWithTokenId(): void
    {
        DatabaseMock::setQueryResult(API_LOG_TABLE, [['total' => '10']]);
        $stats = Api::getStats(5, 30);
        $this->assertIsArray($stats);
        $queries = DatabaseMock::getExecutedQueries();
        $found = false;
        foreach ($queries as $q) {
            if (stripos($q, 'token_id') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testCleanupLogs(): void
    {
        $GLOBALS['cfg'] = new class {
            public function get($key, $default = null) { return $default; }
        };

        $result = Api::cleanupLogs(30);
        $lastQuery = DatabaseMock::getLastQuery();
        $this->assertStringContainsString('DELETE', $lastQuery);
        $this->assertStringContainsString(API_LOG_TABLE, $lastQuery);

        unset($GLOBALS['cfg']);
    }
}
