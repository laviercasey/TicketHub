<?php

use PHPUnit\Framework\TestCase;

class UserSessionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('UserSession')) {
            require_once INCLUDE_DIR . 'class.usersession.php';
        }
    }

    public function testConstructorSetsUserId(): void
    {
        $session = new UserSession('testuser');
        $this->assertEquals('testuser', $session->userID);
    }

    public function testIsStaffReturnsFalse(): void
    {
        $session = new UserSession('user1');
        $this->assertFalse($session->isStaff());
    }

    public function testIsClientReturnsFalse(): void
    {
        $session = new UserSession('user1');
        $this->assertFalse($session->isClient());
    }

    public function testIsValidReturnsFalse(): void
    {
        $session = new UserSession('user1');
        $this->assertFalse($session->isValid());
    }

    public function testGetIP(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $session = new UserSession('user1');
        $this->assertEquals('192.168.1.100', $session->getIP());
    }

    public function testGetBrowser(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/TestBrowser';
        $session = new UserSession('user1');
        $this->assertEquals('PHPUnit/TestBrowser', $session->getBrowser());
    }

    public function testSessionTokenFormat(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $parts = explode(':', $token);
        $this->assertCount(3, $parts);
    }

    public function testSessionTokenContainsHash(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $parts = explode(':', $token);
        $this->assertEquals(64, strlen($parts[0]));
    }

    public function testSessionTokenContainsTimestamp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $parts = explode(':', $token);
        $this->assertIsNumeric($parts[1]);
        $this->assertGreaterThan(0, (int) $parts[1]);
    }

    public function testSessionTokenContainsIpHash(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $parts = explode(':', $token);
        $expectedIpHash = hash('sha256', '127.0.0.1');
        $this->assertEquals($expectedIpHash, $parts[2]);
    }

    public function testIsValidSessionWithValidToken(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $result = $session->isvalidSession($token);
        $this->assertTrue($result);
        $this->assertTrue($session->validated);
    }

    public function testIsValidSessionWithInvalidToken(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $result = $session->isvalidSession('invalidtoken');
        $this->assertFalse($result);
    }

    public function testIsValidSessionExpiredToken(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $oldTime = time() - 7200;
        $hash = hash('sha256', $oldTime . SESSION_SECRET . 'user1');
        $ipHash = hash('sha256', '10.0.0.1');
        $token = "$hash:$oldTime:$ipHash";

        $result = $session->isvalidSession($token, 3600);
        $this->assertFalse($result);
    }

    public function testIsValidSessionNotExpired(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();

        $result = $session->isvalidSession($token, 3600);
        $this->assertTrue($result);
    }

    public function testIsValidSessionIpCheckPass(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();

        $result = $session->isvalidSession($token, 0, true);
        $this->assertTrue($result);
    }

    public function testIsValidSessionIpCheckFail(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $session2 = new UserSession('user1');

        $result = $session2->isvalidSession($token, 0, true);
        $this->assertFalse($result);
    }

    public function testIsValidSessionTamperedHash(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $session = new UserSession('user1');
        $token = $session->sessionToken();
        $parts = explode(':', $token);
        $parts[0] = str_repeat('a', 64);
        $tampered = implode(':', $parts);

        $result = $session->isvalidSession($tampered);
        $this->assertFalse($result);
    }

    public function testRefreshSessionDoesNothing(): void
    {
        $session = new UserSession('user1');
        $session->refreshSession();
        $this->assertTrue(true);
    }

    public function testSessionTokenUniquePerUser(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $session1 = new UserSession('user1');
        $session2 = new UserSession('user2');
        $this->assertNotEquals($session1->sessionToken(), $session2->sessionToken());
    }

    public function testClientSessionClassExists(): void
    {
        $this->assertTrue(class_exists('ClientSession'));
    }

    public function testStaffSessionClassExists(): void
    {
        $this->assertTrue(class_exists('StaffSession'));
    }
}
