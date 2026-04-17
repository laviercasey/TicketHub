<?php

use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Captcha')) {
            require_once INCLUDE_DIR . 'class.captcha.php';
        }
    }

    public function testConstructorDefaultLength(): void
    {
        $captcha = new Captcha(6);
        $this->assertEquals(6, strlen($captcha->hash));
    }

    public function testConstructorCustomLength(): void
    {
        $captcha = new Captcha(4);
        $this->assertEquals(4, strlen($captcha->hash));
    }

    public function testConstructorHashIsUppercase(): void
    {
        $captcha = new Captcha(8);
        $this->assertEquals(strtoupper($captcha->hash), $captcha->hash);
    }

    public function testConstructorHashIsAlphanumeric(): void
    {
        $captcha = new Captcha(10);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPRSTUVWXYZ23456789]+$/', $captcha->hash);
    }

    public function testConstructorSetFont(): void
    {
        $captcha = new Captcha(6, 32);
        $this->assertEquals(32, $captcha->font);
    }

    public function testConstructorSmallFontDefaultsTo28(): void
    {
        $captcha = new Captcha(6, 5);
        $this->assertEquals(28, $captcha->font);
    }

    public function testConstructorHashIsUnique(): void
    {
        $captcha1 = new Captcha(6);
        $captcha2 = new Captcha(6);
        $this->assertNotEquals($captcha1->hash, $captcha2->hash);
    }

    public function testBackwardCompatBgParameterIgnored(): void
    {
        $captcha = new Captcha(5, 28, '/some/path/');
        $this->assertEquals(5, strlen($captcha->hash));
    }

    public function testHashDoesNotContainAmbiguousChars(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $captcha = new Captcha(10);
            $this->assertDoesNotMatchRegularExpression('/[01ILOQ]/', $captcha->hash);
        }
    }
}
