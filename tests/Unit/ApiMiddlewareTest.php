<?php

use PHPUnit\Framework\TestCase;

class ApiMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('ApiMiddleware')) {
            require_once INCLUDE_DIR . 'class.apimiddleware.php';
        }

        DatabaseMock::reset();
    }

    public function testConstructorDefaults(): void
    {
        $mw = new ApiMiddleware();
        $this->assertNull($mw->getToken());
        $this->assertFalse($mw->skip_auth);
    }

    public function testSkipAuth(): void
    {
        $mw = new ApiMiddleware();
        $mw->skipAuth();
        $this->assertTrue($mw->skip_auth);
    }

    public function testGetToken(): void
    {
        $mw = new ApiMiddleware();
        $this->assertNull($mw->getToken());
    }

    public function testHasPermissionNoToken(): void
    {
        $mw = new ApiMiddleware();
        $this->assertFalse($mw->hasPermission('tickets:read'));
    }

    public function testGetResponseTime(): void
    {
        $mw = new ApiMiddleware();
        usleep(1000);
        $time = $mw->getResponseTime();
        $this->assertGreaterThanOrEqual(0, $time);
    }

    public function testExtractTokenFromAuthorizationHeader(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123token';
        $mw = new ApiMiddleware();
        $token = $mw->extractToken();
        $this->assertEquals('abc123token', $token);
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testExtractTokenFromAuthorizationDirect(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'directtoken123';
        $mw = new ApiMiddleware();
        $token = $mw->extractToken();
        $this->assertEquals('directtoken123', $token);
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testExtractTokenFromGetParamNotSupported(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_GET['api_token'] = 'gettoken123';
        $mw = new ApiMiddleware();
        $token = $mw->extractToken();
        $this->assertNull($token);
        unset($_GET['api_token']);
    }

    public function testExtractTokenFromPostParamNotSupported(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_POST['api_token'] = 'posttoken123';
        $mw = new ApiMiddleware();
        $token = $mw->extractToken();
        $this->assertNull($token);
        unset($_POST['api_token']);
    }

    public function testExtractTokenNone(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_GET['api_token']);
        unset($_POST['api_token']);
        $mw = new ApiMiddleware();
        $token = $mw->extractToken();
        $this->assertNull($token);
    }
}
