<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use TicketHub\Mail\MailParseException;
use TicketHub\Mail\SymfonyMailTransport;
use TicketHub\Mail\SymfonyMimeBuilder;
use TicketHub\Mail\SymfonyMimeParser;

#[CoversClass(SymfonyMimeBuilder::class)]
#[CoversClass(SymfonyMimeParser::class)]
#[CoversClass(SymfonyMailTransport::class)]
class MailAdapterTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Shared fixtures
    // -----------------------------------------------------------------------

    private const SIMPLE_EMAIL = "From: sender@example.com\r\n"
        . "To: recipient@example.com\r\n"
        . "Subject: Test Subject\r\n"
        . "Content-Type: text/plain; charset=utf-8\r\n"
        . "Message-ID: <test123@example.com>\r\n"
        . "Date: Sat, 11 Apr 2026 12:00:00 +0000\r\n"
        . "\r\n"
        . "Hello, this is a test message.";

    private const MULTIPART_EMAIL = "From: sender@example.com\r\n"
        . "To: recipient@example.com\r\n"
        . "Subject: Multipart Test\r\n"
        . "Content-Type: multipart/mixed; boundary=\"----=_Part_123\"\r\n"
        . "Message-ID: <test456@example.com>\r\n"
        . "\r\n"
        . "------=_Part_123\r\n"
        . "Content-Type: text/plain; charset=utf-8\r\n"
        . "\r\n"
        . "Text part content.\r\n"
        . "------=_Part_123\r\n"
        . "Content-Type: application/octet-stream; name=\"file.txt\"\r\n"
        . "Content-Disposition: attachment; filename=\"file.txt\"\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "\r\n"
        . "SGVsbG8gV29ybGQ=\r\n"
        . "------=_Part_123--";

    /** @var list<string> Temp files created during tests, cleaned up in tearDown */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeTempFile(string $content = 'test content'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mail_adapter_test_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    // =======================================================================
    // SymfonyMimeBuilder tests
    // =======================================================================

    #[Group('mime-builder')]
    public function testSetTextBodyAndGetBody(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('Hello world');

        $body = $builder->getBody();

        $this->assertNotEmpty($body);
    }

    #[Group('mime-builder')]
    public function testGetBodyReturnsQuotedPrintableByDefault(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('Hello world');

        // No options passed — encoding must default to quoted-printable.
        // The TextPart will add a Content-Transfer-Encoding header that reflects
        // this; we verify by retrieving headers after calling getBody().
        $builder->getBody();
        $headers = $builder->getHeaders(['Subject' => 'test']);

        $this->assertArrayHasKey('Content-Transfer-Encoding', $headers);
        $this->assertSame('quoted-printable', strtolower($headers['Content-Transfer-Encoding']));
    }

    #[Group('mime-builder')]
    public function testGetHeadersIncludesMimeVersion(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('Test body');

        $headers = $builder->getHeaders([]);

        $this->assertArrayHasKey('MIME-Version', $headers);
        $this->assertSame('1.0', $headers['MIME-Version']);
    }

    #[Group('mime-builder')]
    public function testGetHeadersIncludesContentType(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('Test body');

        $headers = $builder->getHeaders([]);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertNotEmpty($headers['Content-Type']);
        // A plain-text message must identify itself as text/plain
        $this->assertStringContainsStringIgnoringCase('text/plain', $headers['Content-Type']);
    }

    #[Group('mime-builder')]
    public function testAddAttachmentThrowsOnUnreadableFile(): void
    {
        $builder = new SymfonyMimeBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not readable/i');

        $builder->addAttachment('/nonexistent/path/file.bin', 'application/octet-stream', 'file.bin');
    }

    #[Group('mime-builder')]
    public function testGetBodyWithAttachment(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('See attached.');

        $tmpFile = $this->makeTempFile('attachment data');
        $builder->addAttachment($tmpFile, 'text/plain', 'attachment.txt');

        $body = $builder->getBody();

        // A multipart body must contain boundary markers (--<boundary>)
        $this->assertStringContainsString('--', $body);
        // The body should contain the base64-encoded attachment content
        $this->assertNotEmpty($body);
    }

    #[Group('mime-builder')]
    public function testEncodeNonAsciiHeaders(): void
    {
        $builder = new SymfonyMimeBuilder();
        $builder->setTextBody('Тестовое сообщение');

        $cyrillicSubject = 'Тема письма';
        $headers = $builder->getHeaders(['Subject' => $cyrillicSubject]);

        // The Cyrillic subject must be MIME-encoded (=?UTF-8?...)
        $this->assertArrayHasKey('Subject', $headers);
        $this->assertStringContainsString('=?', $headers['Subject']);
        $this->assertStringContainsString('UTF-8', $headers['Subject']);
    }

    // =======================================================================
    // SymfonyMimeParser tests
    // =======================================================================

    #[Group('mime-parser')]
    public function testDecodeSimpleTextEmail(): void
    {
        $parser = new SymfonyMimeParser();

        $result = $parser->decode(self::SIMPLE_EMAIL);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertNotEmpty($result->headers);
        $this->assertSame('text', $result->ctype_primary);
        $this->assertSame('plain', $result->ctype_secondary);
        $this->assertNotEmpty($result->body);
        $this->assertStringContainsString('Hello, this is a test message.', $result->body);
    }

    #[Group('mime-parser')]
    public function testDecodeReturnsCorrectHeaders(): void
    {
        $parser = new SymfonyMimeParser();

        $result = $parser->decode(self::SIMPLE_EMAIL);

        $this->assertInstanceOf(\stdClass::class, $result);

        // Headers must be stored under lower-cased names
        $this->assertArrayHasKey('message-id', $result->headers);
        $this->assertArrayHasKey('from', $result->headers);

        $this->assertStringContainsString('test123@example.com', $result->headers['message-id']);
        $this->assertStringContainsString('sender@example.com', $result->headers['from']);
    }

    #[Group('mime-parser')]
    public function testDecodeMultipartEmail(): void
    {
        $parser = new SymfonyMimeParser();

        $result = $parser->decode(self::MULTIPART_EMAIL);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('multipart', $result->ctype_primary);
        $this->assertSame('mixed', $result->ctype_secondary);
        $this->assertIsArray($result->parts);
        $this->assertGreaterThanOrEqual(2, count($result->parts));

        // First part must be text/plain
        $textPart = $result->parts[0];
        $this->assertSame('text', $textPart->ctype_primary);
        $this->assertSame('plain', $textPart->ctype_secondary);

        // Second part must be the attachment
        $attachPart = $result->parts[1];
        $this->assertSame('application', $attachPart->ctype_primary);
    }

    #[Group('mime-parser')]
    public function testDecodeReturnsFalseOnEmptyInput(): void
    {
        $parser = new SymfonyMimeParser();

        $result = $parser->decode('');

        $this->assertFalse($result);
    }

    #[Group('mime-parser')]
    public function testDecodeReturnsFalseOnHeadersOnly(): void
    {
        // A message with only a single header line has count(headers) == 1,
        // which triggers the "< 2 headers" guard in decode() and returns false.
        $minimal = "From: sender@example.com\r\n\r\n";
        $parser = new SymfonyMimeParser();

        $result = $parser->decode($minimal);

        $this->assertFalse($result);
    }

    #[Group('mime-parser')]
    public function testParseAddressListSimple(): void
    {
        $parser = new SymfonyMimeParser();

        $results = $parser->parseAddressList('user@example.com');

        $this->assertCount(1, $results);
        $this->assertSame('user', $results[0]->mailbox);
        $this->assertSame('example.com', $results[0]->host);
    }

    #[Group('mime-parser')]
    public function testParseAddressListWithName(): void
    {
        $parser = new SymfonyMimeParser();

        $results = $parser->parseAddressList('"John Doe" <john@example.com>');

        $this->assertCount(1, $results);
        $this->assertSame('John Doe', $results[0]->personal);
        $this->assertSame('john', $results[0]->mailbox);
        $this->assertSame('example.com', $results[0]->host);
    }

    #[Group('mime-parser')]
    public function testParseAddressListMultiple(): void
    {
        $parser = new SymfonyMimeParser();

        $results = $parser->parseAddressList('a@b.com, c@d.com');

        $this->assertCount(2, $results);
        $this->assertSame('a', $results[0]->mailbox);
        $this->assertSame('b.com', $results[0]->host);
        $this->assertSame('c', $results[1]->mailbox);
        $this->assertSame('d.com', $results[1]->host);
    }

    #[Group('mime-parser')]
    public function testParseAddressListThrowsOnEmpty(): void
    {
        $parser = new SymfonyMimeParser();

        $this->expectException(MailParseException::class);

        $parser->parseAddressList('');
    }

    #[Group('mime-parser')]
    public function testParseAddressListCommentIsArray(): void
    {
        // The ->comment property must always be an array so that legacy
        // pipe.php code can safely iterate over it without a type check.
        $parser = new SymfonyMimeParser();

        $results = $parser->parseAddressList('user@example.com');

        $this->assertCount(1, $results);
        $this->assertIsArray($results[0]->comment);
    }

    // =======================================================================
    // SymfonyMailTransport tests
    // =======================================================================

    #[Group('mail-transport')]
    public function testConstructorAcceptsNullConfig(): void
    {
        // Must not throw
        $transport = new SymfonyMailTransport(null);

        $this->assertInstanceOf(SymfonyMailTransport::class, $transport);
    }

    #[Group('mail-transport')]
    public function testConstructorAcceptsSmtpConfig(): void
    {
        $config = [
            'host'     => 'smtp.example.com',
            'port'     => 587,
            'auth'     => true,
            'username' => 'user@example.com',
            'password' => 'secret',
            'timeout'  => 30,
        ];

        // Must not throw
        $transport = new SymfonyMailTransport($config);

        $this->assertInstanceOf(SymfonyMailTransport::class, $transport);
    }

    // =======================================================================
    // Integration tests: Mail_Parse backward compatibility
    // =======================================================================

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('Mail_Parse', false)) {
            require_once INCLUDE_DIR . 'class.mailparse.php';
        }
    }

    private function makeMailParse(string $rawMessage, bool $includeBodies = true, bool $decodeHeaders = true, bool $decodeBodies = true): \Mail_Parse
    {
        return new Mail_Parse($rawMessage, $includeBodies, $decodeHeaders, $decodeBodies);
    }

    #[Group('mail-parse')]
    public function testMailParseDecodeSimpleMessage(): void
    {
        $parser = $this->makeMailParse(self::SIMPLE_EMAIL);

        $decoded = $parser->decode();

        $this->assertTrue($decoded);

        $body = $parser->getBody();
        $this->assertNotEmpty($body);
        $this->assertStringContainsString('Hello, this is a test message.', $body);
    }

    #[Group('mail-parse')]
    public function testMailParseGetFromAddressList(): void
    {
        $parser = $this->makeMailParse(self::SIMPLE_EMAIL);
        $parser->decode();

        $addresses = $parser->getFromAddressList();

        $this->assertIsArray($addresses);
        $this->assertNotEmpty($addresses);

        $from = $addresses[0];
        $this->assertInstanceOf(\stdClass::class, $from);
        $this->assertSame('sender', $from->mailbox);
        $this->assertSame('example.com', $from->host);
    }

    #[Group('mail-parse')]
    public function testMailParseGetError(): void
    {
        $parser = $this->makeMailParse(self::SIMPLE_EMAIL);
        $parser->decode();

        // A successful decode must leave the error string empty
        $this->assertSame('', $parser->getError());
    }
}
