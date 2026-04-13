<?php

use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('ApiToken')) {
            require_once INCLUDE_DIR . 'class.apitoken.php';
        }

        DatabaseMock::reset();
    }

    private function makeTokenRow(array $overrides = []): array
    {
        return array_merge([
            'token_id' => 1,
            'token' => hash('sha256', 'test-token'),
            'name' => 'Test Token',
            'description' => 'Test description',
            'staff_id' => 1,
            'token_type' => 'permanent',
            'permissions' => '["tickets:read","tickets:write"]',
            'ip_whitelist' => null,
            'ip_check_enabled' => 0,
            'rate_limit' => 1000,
            'rate_window' => 3600,
            'is_active' => 1,
            'expires_at' => null,
            'last_used_at' => null,
            'last_used_ip' => null,
            'last_used_endpoint' => null,
            'total_requests' => 0,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupTokenLookup(array $overrides = []): void
    {
        $row = $this->makeTokenRow($overrides);
        DatabaseMock::setQueryResult(API_TOKEN_TABLE, [$row]);
    }

    public function testConstructorLoadsToken(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertEquals(1, $token->getId());
    }

    public function testConstructorNotFound(): void
    {
        $token = new ApiToken(999);
        $this->assertEquals(0, $token->getId());
    }

    public function testGetName(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertEquals('Test Token', $token->getName());
    }

    public function testGetDescription(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertEquals('Test description', $token->getDescription());
    }

    public function testGetStaffId(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertEquals(1, $token->getStaffId());
    }

    public function testGetType(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertEquals('permanent', $token->getType());
    }

    public function testIsActive(): void
    {
        $this->setupTokenLookup(['is_active' => 1]);
        $token = new ApiToken(1);
        $this->assertEquals(1, $token->isActive());
    }

    public function testIsNotActive(): void
    {
        $this->setupTokenLookup(['is_active' => 0]);
        $token = new ApiToken(1);
        $this->assertEquals(0, $token->isActive());
    }

    public function testIsExpiredWithPastDate(): void
    {
        $this->setupTokenLookup(['expires_at' => '2020-01-01 00:00:00']);
        $token = new ApiToken(1);
        $this->assertTrue($token->isExpired());
    }

    public function testIsNotExpiredWithFutureDate(): void
    {
        $this->setupTokenLookup(['expires_at' => '2030-01-01 00:00:00']);
        $token = new ApiToken(1);
        $this->assertFalse($token->isExpired());
    }

    public function testIsNotExpiredWithNoExpiration(): void
    {
        $this->setupTokenLookup(['expires_at' => null]);
        $token = new ApiToken(1);
        $this->assertFalse($token->isExpired());
    }

    public function testGetRateLimit(): void
    {
        $this->setupTokenLookup(['rate_limit' => 500]);
        $token = new ApiToken(1);
        $this->assertEquals(500, $token->getRateLimit());
    }

    public function testGetRateWindow(): void
    {
        $this->setupTokenLookup(['rate_window' => 1800]);
        $token = new ApiToken(1);
        $this->assertEquals(1800, $token->getRateWindow());
    }

    public function testGetPermissions(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $perms = $token->getPermissions();
        $this->assertIsArray($perms);
        $this->assertContains('tickets:read', $perms);
        $this->assertContains('tickets:write', $perms);
    }

    public function testGetPermissionsEmpty(): void
    {
        $this->setupTokenLookup(['permissions' => null]);
        $token = new ApiToken(1);
        $this->assertEmpty($token->getPermissions());
    }

    public function testHasPermissionExactMatch(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertTrue($token->hasPermission('tickets:read'));
    }

    public function testHasPermissionNotGranted(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertFalse($token->hasPermission('admin:users'));
    }

    public function testHasPermissionAdminWildcard(): void
    {
        $this->setupTokenLookup(['permissions' => '["admin:*"]']);
        $token = new ApiToken(1);
        $this->assertTrue($token->hasPermission('tickets:read'));
        $this->assertTrue($token->hasPermission('users:delete'));
    }

    public function testHasPermissionCategoryWildcard(): void
    {
        $this->setupTokenLookup(['permissions' => '["tickets:*"]']);
        $token = new ApiToken(1);
        $this->assertTrue($token->hasPermission('tickets:read'));
        $this->assertTrue($token->hasPermission('tickets:write'));
        $this->assertFalse($token->hasPermission('users:read'));
    }

    public function testGetIpWhitelist(): void
    {
        $this->setupTokenLookup(['ip_whitelist' => '["192.168.1.1","10.0.0.0/8"]']);
        $token = new ApiToken(1);
        $list = $token->getIpWhitelist();
        $this->assertIsArray($list);
        $this->assertCount(2, $list);
    }

    public function testGetIpWhitelistEmpty(): void
    {
        $this->setupTokenLookup(['ip_whitelist' => null]);
        $token = new ApiToken(1);
        $this->assertEmpty($token->getIpWhitelist());
    }

    public function testIsIpAllowedWhenCheckDisabled(): void
    {
        $this->setupTokenLookup(['ip_check_enabled' => 0]);
        $token = new ApiToken(1);
        $this->assertTrue($token->isIpAllowed('1.2.3.4'));
    }

    public function testIsIpAllowedEmptyWhitelist(): void
    {
        $this->setupTokenLookup(['ip_check_enabled' => 1, 'ip_whitelist' => '[]']);
        $token = new ApiToken(1);
        $this->assertTrue($token->isIpAllowed('1.2.3.4'));
    }

    public function testIsIpAllowedExactMatch(): void
    {
        $this->setupTokenLookup([
            'ip_check_enabled' => 1,
            'ip_whitelist' => '["192.168.1.100"]',
        ]);
        $token = new ApiToken(1);
        $this->assertTrue($token->isIpAllowed('192.168.1.100'));
        $this->assertFalse($token->isIpAllowed('192.168.1.200'));
    }

    public function testIsIpAllowedCidr(): void
    {
        $this->setupTokenLookup([
            'ip_check_enabled' => 1,
            'ip_whitelist' => '["10.0.0.0/8"]',
        ]);
        $token = new ApiToken(1);
        $this->assertTrue($token->isIpAllowed('10.0.0.1'));
        $this->assertTrue($token->isIpAllowed('10.255.255.255'));
        $this->assertFalse($token->isIpAllowed('11.0.0.1'));
    }

    public function testIpInCidr(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertTrue($token->ipInCidr('192.168.1.100', '192.168.1.0/24'));
        $this->assertFalse($token->ipInCidr('192.168.2.100', '192.168.1.0/24'));
    }

    public function testIpInCidrSmallSubnet(): void
    {
        $this->setupTokenLookup();
        $token = new ApiToken(1);
        $this->assertTrue($token->ipInCidr('10.0.0.1', '10.0.0.0/30'));
        $this->assertFalse($token->ipInCidr('10.0.0.5', '10.0.0.0/30'));
    }

    public function testValidateActiveToken(): void
    {
        $this->setupTokenLookup([
            'is_active' => 1,
            'expires_at' => null,
        ]);
        $token = new ApiToken(1);
        $this->assertTrue($token->validate());
    }

    public function testValidateInactiveToken(): void
    {
        $this->setupTokenLookup(['is_active' => 0]);
        $token = new ApiToken(1);
        $this->assertFalse($token->validate());
    }

    public function testValidateExpiredToken(): void
    {
        $this->setupTokenLookup([
            'is_active' => 1,
            'expires_at' => '2020-01-01 00:00:00',
        ]);
        $token = new ApiToken(1);
        $this->assertFalse($token->validate());
    }

    public function testValidateWithIpCheck(): void
    {
        $this->setupTokenLookup([
            'is_active' => 1,
            'ip_check_enabled' => 1,
            'ip_whitelist' => '["192.168.1.0/24"]',
        ]);
        $token = new ApiToken(1);
        $this->assertTrue($token->validate('192.168.1.50'));
        $this->assertFalse($token->validate('10.0.0.1'));
    }

    public function testGenerateToken(): void
    {
        $token = ApiToken::generateToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerateTokenUnique(): void
    {
        $token1 = ApiToken::generateToken();
        $token2 = ApiToken::generateToken();
        $this->assertNotEquals($token1, $token2);
    }

    public function testCreateValidationNoName(): void
    {
        $GLOBALS['cfg'] = new class {
            public function get($key, $default = null) { return $default; }
        };

        $errors = [];
        $token = new ApiToken(0);
        $result = $token->create([
            'name' => '',
            'description' => 'Test',
        ], $errors);
        $this->assertNull($result);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testLoadByTokenString(): void
    {
        $tokenHash = hash('sha256', 'test');
        $this->setupTokenLookup(['token' => $tokenHash]);
        $token = new ApiToken($tokenHash);
        $this->assertEquals(1, $token->getId());
    }

    public function testGetTotalRequests(): void
    {
        $this->setupTokenLookup(['total_requests' => 42]);
        $token = new ApiToken(1);
        $this->assertEquals(42, $token->getTotalRequests());
    }
}
