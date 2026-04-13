<?php

use PHPUnit\Framework\TestCase;

class ApiMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('ApiMetrics')) {
            require_once INCLUDE_DIR . 'class.apimetrics.php';
        }

        $GLOBALS['__db'] = null;
        DatabaseMock::reset();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['__db']);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('ApiMetrics'));
    }

    public function testGetRealtimeStatsReturnsStructure(): void
    {
        $stats = ApiMetrics::getRealtimeStats(24);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('successful_requests', $stats);
        $this->assertArrayHasKey('failed_requests', $stats);
        $this->assertArrayHasKey('avg_response_time', $stats);
        $this->assertArrayHasKey('requests_per_hour', $stats);
        $this->assertArrayHasKey('active_tokens', $stats);
        $this->assertArrayHasKey('error_rate', $stats);
        $this->assertArrayHasKey('top_endpoints', $stats);
        $this->assertArrayHasKey('top_tokens', $stats);
        $this->assertArrayHasKey('status_distribution', $stats);
    }

    public function testGetRealtimeStatsDefaultValues(): void
    {
        $stats = ApiMetrics::getRealtimeStats(24);
        $this->assertEquals(0, $stats['total_requests']);
        $this->assertEquals(0, $stats['successful_requests']);
        $this->assertEquals(0, $stats['failed_requests']);
        $this->assertEquals(0, $stats['avg_response_time']);
        $this->assertEquals(0, $stats['requests_per_hour']);
        $this->assertEquals(0, $stats['error_rate']);
    }

    public function testGetHistoricalDataReturnsArray(): void
    {
        $data = ApiMetrics::getHistoricalData(7, 'day');
        $this->assertIsArray($data);
    }

    public function testGetHistoricalDataAutoSwitchesToDay(): void
    {
        $data = ApiMetrics::getHistoricalData(5, 'hour');
        $this->assertIsArray($data);
    }

    public function testGetHistoricalDataHourInterval(): void
    {
        $data = ApiMetrics::getHistoricalData(1, 'hour');
        $this->assertIsArray($data);
    }

    public function testGetPerformanceMetricsReturnsStructure(): void
    {
        $metrics = ApiMetrics::getPerformanceMetrics(24);
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('avg_response_time', $metrics);
        $this->assertArrayHasKey('min_response_time', $metrics);
        $this->assertArrayHasKey('max_response_time', $metrics);
        $this->assertArrayHasKey('p50_response_time', $metrics);
        $this->assertArrayHasKey('p95_response_time', $metrics);
        $this->assertArrayHasKey('p99_response_time', $metrics);
        $this->assertArrayHasKey('slow_requests', $metrics);
        $this->assertArrayHasKey('slowest_endpoints', $metrics);
    }

    public function testGetPerformanceMetricsDefaultValues(): void
    {
        $metrics = ApiMetrics::getPerformanceMetrics(24);
        $this->assertEquals(0, $metrics['avg_response_time']);
        $this->assertEquals(0, $metrics['min_response_time']);
        $this->assertEquals(0, $metrics['max_response_time']);
        $this->assertEquals(0, $metrics['slow_requests']);
    }

    public function testGetErrorAnalysisReturnsStructure(): void
    {
        $analysis = ApiMetrics::getErrorAnalysis(24);
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('total_errors', $analysis);
        $this->assertArrayHasKey('error_rate', $analysis);
        $this->assertArrayHasKey('errors_by_status', $analysis);
        $this->assertArrayHasKey('errors_by_endpoint', $analysis);
        $this->assertArrayHasKey('recent_errors', $analysis);
    }

    public function testGetErrorAnalysisDefaultValues(): void
    {
        $analysis = ApiMetrics::getErrorAnalysis(24);
        $this->assertEquals(0, $analysis['total_errors']);
        $this->assertEquals(0, $analysis['error_rate']);
        $this->assertIsArray($analysis['errors_by_status']);
        $this->assertIsArray($analysis['recent_errors']);
    }

    public function testGetTokenStatsReturnsStructure(): void
    {
        $stats = ApiMetrics::getTokenStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_tokens', $stats);
        $this->assertArrayHasKey('active_tokens', $stats);
        $this->assertArrayHasKey('inactive_tokens', $stats);
        $this->assertArrayHasKey('token_usage', $stats);
    }

    public function testGetTokenStatsDefaultValues(): void
    {
        $stats = ApiMetrics::getTokenStats();
        $this->assertEquals(0, $stats['total_tokens']);
        $this->assertEquals(0, $stats['active_tokens']);
        $this->assertEquals(0, $stats['inactive_tokens']);
    }

    public function testHealthCheckReturnsStructure(): void
    {
        $health = ApiMetrics::healthCheck();
        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('timestamp', $health);
    }

    public function testHealthCheckTimestampIsValid(): void
    {
        $health = ApiMetrics::healthCheck();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $health['timestamp']);
    }

    public function testHealthCheckHasComponents(): void
    {
        $health = ApiMetrics::healthCheck();
        $this->assertIsArray($health['checks']);
        foreach ($health['checks'] as $check) {
            $this->assertArrayHasKey('component', $check);
            $this->assertArrayHasKey('status', $check);
            $this->assertArrayHasKey('message', $check);
        }
    }

    public function testCheckAlertsReturnsArray(): void
    {
        $alerts = ApiMetrics::checkAlerts();
        $this->assertIsArray($alerts);
    }

    public function testGetUsageTrendsReturnsStructure(): void
    {
        $trends = ApiMetrics::getUsageTrends(30);
        $this->assertIsArray($trends);
        $this->assertArrayHasKey('daily_requests', $trends);
        $this->assertArrayHasKey('growth_rate', $trends);
        $this->assertArrayHasKey('popular_hours', $trends);
        $this->assertArrayHasKey('busiest_day', $trends);
    }

    public function testGetUsageTrendsDefaultValues(): void
    {
        $trends = ApiMetrics::getUsageTrends(30);
        $this->assertEquals(0, $trends['growth_rate']);
        $this->assertEquals('N/A', $trends['busiest_day']);
        $this->assertIsArray($trends['daily_requests']);
        $this->assertIsArray($trends['popular_hours']);
    }

    public function testCleanupOldLogsReturnsStructure(): void
    {
        $result = ApiMetrics::cleanupOldLogs(30);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('api_logs_deleted', $result);
        $this->assertArrayHasKey('audit_logs_deleted', $result);
        $this->assertArrayHasKey('rate_limits_deleted', $result);
    }

    public function testGetSummaryReturnsAllSections(): void
    {
        $summary = ApiMetrics::getSummary(24);
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('realtime', $summary);
        $this->assertArrayHasKey('performance', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('tokens', $summary);
        $this->assertArrayHasKey('health', $summary);
        $this->assertArrayHasKey('alerts', $summary);
        $this->assertArrayHasKey('trends', $summary);
        $this->assertArrayHasKey('historical', $summary);
    }

    public function testGetSummaryLargeHoursUsesDays30(): void
    {
        $summary = ApiMetrics::getSummary(200);
        $this->assertIsArray($summary);
    }

    public function testGetRealtimeStatsCustomHours(): void
    {
        $stats = ApiMetrics::getRealtimeStats(1);
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['total_requests']);
    }
}
