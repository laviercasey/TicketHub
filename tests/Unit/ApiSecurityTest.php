<?php

use PHPUnit\Framework\TestCase;

class ApiSecurityTest extends TestCase
{
    public function testGetSecurityHeaders(): void
    {
        $headers = ApiSecurity::getSecurityHeaders();

        $this->assertIsArray($headers);
        $this->assertEquals('DENY', $headers['X-Frame-Options']);
        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertEquals('1; mode=block', $headers['X-XSS-Protection']);
        $this->assertArrayHasKey('Referrer-Policy', $headers);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('Permissions-Policy', $headers);
    }

    public function testSanitizeInputString(): void
    {
        $this->assertEquals('hello', ApiSecurity::sanitizeInput('  hello  '));
        $this->assertEquals('testinjection', ApiSecurity::sanitizeInput("test\0injection"));
    }

    public function testSanitizeInputArray(): void
    {
        $input = [
            'name' => "  John\0  ",
            'email' => '  test@example.com  '
        ];
        $result = ApiSecurity::sanitizeInput($input);

        $this->assertEquals('John', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testSanitizeInputNonString(): void
    {
        $this->assertEquals(42, ApiSecurity::sanitizeInput(42));
        $this->assertTrue(ApiSecurity::sanitizeInput(true));
        $this->assertNull(ApiSecurity::sanitizeInput(null));
    }

    public function testValidateEmailValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateEmail('user@example.com', $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateEmailEmpty(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateEmail('', $errors));
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateEmailInvalid(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateEmail('not-an-email', $errors));
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateEmailTooLong(): void
    {
        $errors = [];
        $longEmail = str_repeat('a', 250) . '@test.com';
        $this->assertFalse(ApiSecurity::validateEmail($longEmail, $errors));
    }

    public function testValidateEmailWithDangerousChars(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateEmail('user<script>@test.com', $errors));
    }

    public function testValidateEmailCustomField(): void
    {
        $errors = [];
        ApiSecurity::validateEmail('', $errors, 'contact_email');
        $this->assertArrayHasKey('contact_email', $errors);
    }

    public function testValidateIntegerValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateInteger(42, $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateIntegerInvalid(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateInteger('abc', $errors));
        $this->assertArrayHasKey('id', $errors);
    }

    public function testValidateIntegerMinMax(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateInteger(5, $errors, 'val', 10));
        $this->assertArrayHasKey('val', $errors);

        $errors = [];
        $this->assertFalse(ApiSecurity::validateInteger(100, $errors, 'val', null, 50));
        $this->assertArrayHasKey('val', $errors);
    }

    public function testValidateStringLengthValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateStringLength('hello', $errors, 'name', 1, 10));
        $this->assertEmpty($errors);
    }

    public function testValidateStringLengthTooShort(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateStringLength('hi', $errors, 'name', 5));
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidateStringLengthTooLong(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateStringLength('hello world', $errors, 'name', null, 5));
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidateEnumValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateEnum('open', ['open', 'closed'], $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateEnumInvalid(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateEnum('pending', ['open', 'closed'], $errors));
        $this->assertArrayHasKey('field', $errors);
    }

    public function testValidateDateTimeValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateDateTime('2026-01-15T10:30:00+00:00', $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateDateTimeEmpty(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateDateTime('', $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateDateTimeInvalid(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateDateTime('not-a-date', $errors));
        $this->assertArrayHasKey('date', $errors);
    }

    public function testDetectSqlInjectionUnionSelect(): void
    {
        $this->assertTrue(ApiSecurity::detectSqlInjection("1 UNION SELECT * FROM users"));
    }

    public function testDetectSqlInjectionDropTable(): void
    {
        $this->assertTrue(ApiSecurity::detectSqlInjection("1; DROP TABLE users"));
    }

    public function testDetectSqlInjectionComment(): void
    {
        $this->assertTrue(ApiSecurity::detectSqlInjection("admin'--"));
    }

    public function testDetectSqlInjectionCleanInput(): void
    {
        $this->assertFalse(ApiSecurity::detectSqlInjection("John Doe"));
        $this->assertFalse(ApiSecurity::detectSqlInjection("test@example.com"));
    }

    public function testDetectSqlInjectionNonString(): void
    {
        $this->assertFalse(ApiSecurity::detectSqlInjection(42));
        $this->assertFalse(ApiSecurity::detectSqlInjection(null));
    }

    public function testDetectXssScript(): void
    {
        $this->assertTrue(ApiSecurity::detectXss('<script>alert("xss")</script>'));
    }

    public function testDetectXssJavascriptProtocol(): void
    {
        $this->assertTrue(ApiSecurity::detectXss('javascript:alert(1)'));
    }

    public function testDetectXssEventHandler(): void
    {
        $this->assertTrue(ApiSecurity::detectXss('<img onerror=alert(1)>'));
    }

    public function testDetectXssIframe(): void
    {
        $this->assertTrue(ApiSecurity::detectXss('<iframe src="evil.com">'));
    }

    public function testDetectXssCleanInput(): void
    {
        $this->assertFalse(ApiSecurity::detectXss('Hello World'));
        $this->assertFalse(ApiSecurity::detectXss('<p>Normal paragraph</p>'));
    }

    public function testDetectXssNonString(): void
    {
        $this->assertFalse(ApiSecurity::detectXss(42));
    }

    public function testIsPrivateIpPrivateRanges(): void
    {
        $this->assertTrue(ApiSecurity::isPrivateIp('10.0.0.1'));
        $this->assertTrue(ApiSecurity::isPrivateIp('192.168.1.1'));
        $this->assertTrue(ApiSecurity::isPrivateIp('172.16.0.1'));
        $this->assertTrue(ApiSecurity::isPrivateIp('127.0.0.1'));
    }

    public function testIsPrivateIpPublicAddresses(): void
    {
        $this->assertFalse(ApiSecurity::isPrivateIp('8.8.8.8'));
        $this->assertFalse(ApiSecurity::isPrivateIp('1.1.1.1'));
        $this->assertFalse(ApiSecurity::isPrivateIp('203.0.113.1'));
    }

    public function testIsPrivateIpInvalid(): void
    {
        $this->assertFalse(ApiSecurity::isPrivateIp('not-an-ip'));
    }

    public function testValidateTokenFormatValid(): void
    {
        $token = str_repeat('a', 64);
        $this->assertTrue(ApiSecurity::validateTokenFormat($token));
    }

    public function testValidateTokenFormatTooShort(): void
    {
        $this->assertFalse(ApiSecurity::validateTokenFormat('short'));
    }

    public function testValidateTokenFormatSpecialChars(): void
    {
        $token = str_repeat('!', 64);
        $this->assertFalse(ApiSecurity::validateTokenFormat($token));
    }

    public function testValidateOriginNoRestrictions(): void
    {
        $this->assertTrue(ApiSecurity::validateOrigin([]));
    }

    public function testValidateOriginAllowed(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
        $this->assertTrue(ApiSecurity::validateOrigin(['https://example.com']));
    }

    public function testValidateOriginDenied(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.com';
        $this->assertFalse(ApiSecurity::validateOrigin(['https://example.com']));
    }

    public function testValidateOriginExactMatch(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
        $this->assertTrue(ApiSecurity::validateOrigin(['https://example.com']));
    }

    public function testValidateOriginNoHeader(): void
    {
        unset($_SERVER['HTTP_ORIGIN']);
        $this->assertTrue(ApiSecurity::validateOrigin(['https://example.com']));
    }

    public function testGenerateSecureToken(): void
    {
        $token = ApiSecurity::generateSecureToken(64);
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerateSecureTokenCustomLength(): void
    {
        $token = ApiSecurity::generateSecureToken(32);
        $this->assertEquals(32, strlen($token));
    }

    public function testHashSensitiveData(): void
    {
        $data = [
            'username' => 'admin',
            'password' => 'secret123',
            'token' => 'abc123',
            'email' => 'admin@test.com'
        ];

        $result = ApiSecurity::hashSensitiveData($data);

        $this->assertEquals('admin', $result['username']);
        $this->assertEquals('[REDACTED]', $result['password']);
        $this->assertEquals('[REDACTED]', $result['token']);
        $this->assertEquals('admin@test.com', $result['email']);
    }

    public function testHashSensitiveDataCustomFields(): void
    {
        $data = ['secret_key' => 'value', 'public' => 'data'];
        $result = ApiSecurity::hashSensitiveData($data, ['secret_key']);

        $this->assertEquals('[REDACTED]', $result['secret_key']);
        $this->assertEquals('data', $result['public']);
    }

    public function testHashSensitiveDataNonArray(): void
    {
        $this->assertEquals('string', ApiSecurity::hashSensitiveData('string'));
    }

    public function testConstantTimeCompareEqual(): void
    {
        $this->assertTrue(ApiSecurity::constantTimeCompare('secret', 'secret'));
    }

    public function testConstantTimeCompareNotEqual(): void
    {
        $this->assertFalse(ApiSecurity::constantTimeCompare('secret', 'wrong'));
    }

    public function testConstantTimeCompareDifferentLength(): void
    {
        $this->assertFalse(ApiSecurity::constantTimeCompare('short', 'longer-string'));
    }

    public function testGetClientIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_CLIENT_IP']);

        $ip = ApiSecurity::getClientIp();
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function testGetClientIpIgnoresForwardedHeader(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50, 70.41.3.18';
        $ip = ApiSecurity::getClientIp();
        $this->assertEquals('192.168.1.100', $ip);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public function testCheckBruteForceAllowed(): void
    {
        $_SESSION = [];
        $this->assertTrue(ApiSecurity::checkBruteForce('test-user'));
    }

    public function testRecordAndCheckBruteForce(): void
    {
        $_SESSION = [];

        for ($i = 0; $i < 5; $i++) {
            ApiSecurity::recordFailedAttempt('brute-test');
        }

        $this->assertFalse(ApiSecurity::checkBruteForce('brute-test'));
    }

    public function testClearFailedAttempts(): void
    {
        $_SESSION = [];

        for ($i = 0; $i < 5; $i++) {
            ApiSecurity::recordFailedAttempt('clear-test');
        }

        ApiSecurity::clearFailedAttempts('clear-test');
        $this->assertTrue(ApiSecurity::checkBruteForce('clear-test'));
    }

    public function testValidateUrlValid(): void
    {
        $errors = [];
        $this->assertTrue(ApiSecurity::validateUrl('https://example.com', $errors));
        $this->assertEmpty($errors);
    }

    public function testValidateUrlEmpty(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateUrl('', $errors));
        $this->assertArrayHasKey('url', $errors);
    }

    public function testValidateUrlInvalidFormat(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateUrl('not-a-url', $errors));
    }

    public function testValidateUrlFtpNotAllowed(): void
    {
        $errors = [];
        $this->assertFalse(ApiSecurity::validateUrl('ftp://example.com', $errors));
    }

    public function testValidateUrlTooLong(): void
    {
        $errors = [];
        $longUrl = 'https://example.com/' . str_repeat('a', 2048);
        $this->assertFalse(ApiSecurity::validateUrl($longUrl, $errors));
    }
}
