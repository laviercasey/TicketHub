<?php

use PHPUnit\Framework\TestCase;

class MiscTest extends TestCase
{
    public function testRandCodeDefaultLength(): void
    {
        $code = Misc::randCode();
        $this->assertEquals(8, strlen($code));
        $this->assertMatchesRegularExpression('/^[A-F0-9]+$/', $code);
    }

    public function testRandCodeCustomLength(): void
    {
        $code = Misc::randCode(16);
        $this->assertEquals(16, strlen($code));
    }

    public function testRandCodeUniqueness(): void
    {
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = Misc::randCode(12);
        }
        $this->assertCount(100, array_unique($codes));
    }

    public function testRandNumberDefaultLength(): void
    {
        $num = Misc::randNumber();
        $this->assertGreaterThanOrEqual(100000, $num);
        $this->assertLessThanOrEqual(999999, $num);
    }

    public function testRandNumberCustomLength(): void
    {
        $num = Misc::randNumber(4);
        $this->assertGreaterThanOrEqual(1000, $num);
        $this->assertLessThanOrEqual(9999, $num);
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        if (!function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL not available');
        }

        $plaintext = 'Hello, TicketHub!';
        $salt = 'test-salt-key-2026';

        $encrypted = Misc::encrypt($plaintext, $salt);
        $this->assertNotEquals($plaintext, $encrypted);

        $decrypted = Misc::decrypt($encrypted, $salt);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentOutputForDifferentSalts(): void
    {
        if (!function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL not available');
        }

        $plaintext = 'secret data';
        $enc1 = Misc::encrypt($plaintext, 'salt1');
        $enc2 = Misc::encrypt($plaintext, 'salt2');
        $this->assertNotEquals($enc1, $enc2);
    }

    public function testDecryptWithWrongSaltFails(): void
    {
        if (!function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL not available');
        }

        $encrypted = Misc::encrypt('secret', 'correct-salt');
        $decrypted = Misc::decrypt($encrypted, 'wrong-salt');
        $this->assertNotEquals('secret', $decrypted);
    }

    public function testGmtime(): void
    {
        $gmtime = Misc::gmtime();
        $this->assertIsInt($gmtime);
        $this->assertGreaterThan(0, $gmtime);
    }

    public function testGenerateCSRFToken(): void
    {
        $_SESSION = [];
        $token = Misc::generateCSRFToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerateCSRFTokenReturnsExisting(): void
    {
        $_SESSION = [];
        $token1 = Misc::generateCSRFToken();
        $token2 = Misc::generateCSRFToken();
        $this->assertEquals($token1, $token2);
    }

    public function testValidateCSRFTokenCorrect(): void
    {
        $_SESSION = [];
        $token = Misc::generateCSRFToken();
        $this->assertTrue(Misc::validateCSRFToken($token));
    }

    public function testValidateCSRFTokenIncorrect(): void
    {
        $_SESSION = [];
        Misc::generateCSRFToken();
        $this->assertFalse(Misc::validateCSRFToken('wrong-token'));
    }

    public function testValidateCSRFTokenNoSession(): void
    {
        $_SESSION = [];
        $this->assertFalse(Misc::validateCSRFToken('any-token'));
    }

    public function testCsrfFieldContainsHiddenInput(): void
    {
        $_SESSION = [];
        $field = Misc::csrfField();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    public function testTimeDropdownReturnsSelectElement(): void
    {
        $html = Misc::timeDropdown(10, 30);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('</select>', $html);
        $this->assertStringContainsString('10:30', $html);
    }

    public function testTimeDropdownNormalizesHours(): void
    {
        $html = Misc::timeDropdown(25, 0);
        $this->assertStringContainsString('<select', $html);
    }

    public function testTimeDropdownNormalizesMinutes(): void
    {
        $html = Misc::timeDropdown(10, 50);
        $this->assertStringContainsString('<select', $html);
    }

    public function testTimeDropdownDefaultValues(): void
    {
        $html = Misc::timeDropdown();
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="time"', $html);
    }

    public function testTimeDropdownCustomName(): void
    {
        $html = Misc::timeDropdown(0, 0, 'start_time');
        $this->assertStringContainsString('name="start_time"', $html);
    }

    public function testCurrentURL(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['REQUEST_URI'] = '/tickets.php?id=1';

        $url = Misc::currentURL();
        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('example.com', $url);
    }
}
