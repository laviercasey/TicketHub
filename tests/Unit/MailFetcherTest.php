<?php

use PHPUnit\Framework\TestCase;

class MailFetcherTest extends TestCase
{
    private static bool $classLoaded = false;

    public static function setUpBeforeClass(): void
    {
        if (class_exists('MailFetcher', false)) {
            self::$classLoaded = true;
            return;
        }

        if (!class_exists('Mail_Parse', false)) {
            eval('class Mail_Parse {
                public static function parsePriority($header) { return 2; }
            }');
        }

        $code = file_get_contents(INCLUDE_DIR . 'class.mailfetch.php');
        $code = str_replace("require_once(INCLUDE_DIR.'class.mailparse.php');", '', $code);
        $code = str_replace("require_once(INCLUDE_DIR.'class.ticket.php');", '', $code);
        $code = str_replace("require_once(INCLUDE_DIR.'class.dept.php');", '', $code);
        $code = preg_replace('/<\?php/', '', $code, 1);
        $code = str_replace('?>', '', $code);

        eval($code);
        self::$classLoaded = true;
    }

    protected function setUp(): void
    {
        if (!self::$classLoaded) {
            $this->markTestSkipped('MailFetcher could not be loaded');
        }
    }

    public function testConstructorSetsHostname(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP', 'SSL');
        $this->assertEquals('mail.example.com', $fetcher->hostname);
    }

    public function testConstructorSetsUsername(): void
    {
        $fetcher = new MailFetcher('testuser', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertEquals('testuser', $fetcher->username);
    }

    public function testConstructorSetsPassword(): void
    {
        $fetcher = new MailFetcher('user', 'secret123', 'mail.example.com', 993, 'IMAP');
        $this->assertEquals('secret123', $fetcher->password);
    }

    public function testConstructorSetsPort(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertEquals(993, $fetcher->port);
    }

    public function testConstructorProtocolLowercase(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertEquals('imap', $fetcher->protocol);
    }

    public function testConstructorPopForcedToPop3(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 110, 'POP');
        $this->assertEquals('pop3', $fetcher->protocol);
    }

    public function testConstructorPopCaseInsensitive(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 110, 'pop');
        $this->assertEquals('pop3', $fetcher->protocol);
    }

    public function testConstructorSSLEncryption(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP', 'SSL');
        $this->assertEquals('SSL', $fetcher->encryption);
    }

    public function testConstructorNoEncryption(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 143, 'IMAP', '');
        $this->assertEquals('', $fetcher->encryption);
    }

    public function testConstructorDefaultCharset(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertEquals('UTF-8', $fetcher->charset);
    }

    public function testServerStrContainsHost(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertStringContainsString('mail.example.com', $fetcher->serverstr);
    }

    public function testServerStrContainsPort(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertStringContainsString('993', $fetcher->serverstr);
    }

    public function testServerStrContainsProtocol(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertStringContainsString('imap', $fetcher->serverstr);
    }

    public function testServerStrContainsSSL(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP', 'SSL');
        $this->assertStringContainsString('/ssl', $fetcher->serverstr);
    }

    public function testServerStrNoSSLWithoutEncryption(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 143, 'IMAP', '');
        $this->assertStringNotContainsString('/ssl', $fetcher->serverstr);
    }

    public function testServerStrEndsWithINBOX(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertStringEndsWith('INBOX', $fetcher->serverstr);
    }

    public function testServerStrContainsNovalidateCert(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'mail.example.com', 993, 'IMAP');
        $this->assertStringContainsString('novalidate-cert', $fetcher->serverstr);
    }

    public function testGetMimeTypeTextPlain(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 0;
        $struct->subtype = 'PLAIN';
        $this->assertEquals('TEXT/PLAIN', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeTextHtml(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 0;
        $struct->subtype = 'HTML';
        $this->assertEquals('TEXT/HTML', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeMultipart(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 1;
        $struct->subtype = 'MIXED';
        $this->assertEquals('MULTIPART/MIXED', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeApplication(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 3;
        $struct->subtype = 'PDF';
        $this->assertEquals('APPLICATION/PDF', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeImage(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 5;
        $struct->subtype = 'JPEG';
        $this->assertEquals('IMAGE/JPEG', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeNull(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $this->assertEquals('TEXT/PLAIN', $fetcher->getMimeType(null));
    }

    public function testGetMimeTypeNoSubtype(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 0;
        $struct->subtype = '';
        $this->assertEquals('TEXT/PLAIN', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeMessage(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 2;
        $struct->subtype = 'RFC822';
        $this->assertEquals('MESSAGE/RFC822', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeAudio(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 4;
        $struct->subtype = 'MPEG';
        $this->assertEquals('AUDIO/MPEG', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeVideo(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 6;
        $struct->subtype = 'MP4';
        $this->assertEquals('VIDEO/MP4', $fetcher->getMimeType($struct));
    }

    public function testGetMimeTypeOther(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $struct = new \stdClass();
        $struct->type = 7;
        $struct->subtype = 'UNKNOWN';
        $this->assertEquals('OTHER/UNKNOWN', $fetcher->getMimeType($struct));
    }

    public function testMimeEncodeUtf8(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $result = $fetcher->mime_encode('Hello World', null, 'utf-8');
        $this->assertNotEmpty($result);
    }

    public function testMimeEncodeEmptyString(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $result = $fetcher->mime_encode('', null, 'utf-8');
        $this->assertEquals('', $result);
    }

    public function testMimeEncodeWithCharset(): void
    {
        $fetcher = new MailFetcher('user', 'pass', 'host', 993, 'IMAP');
        $result = $fetcher->mime_encode('Test', 'UTF-8', 'UTF-8');
        $this->assertEquals('Test', $result);
    }
}
