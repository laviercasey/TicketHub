<?php

use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Document')) {
            require_once INCLUDE_DIR . 'class.document.php';
        }

        DatabaseMock::reset();
    }

    private function makeDocRow(array $overrides = []): array
    {
        return array_merge([
            'doc_id' => 1,
            'title' => 'User Guide',
            'description' => 'How to use the system',
            'doc_type' => 'file',
            'file_name' => 'guide.pdf',
            'file_key' => 'abc123',
            'file_size' => 1048576,
            'file_mime' => 'application/pdf',
            'external_url' => '',
            'audience' => 'staff',
            'dept_id' => 1,
            'staff_id' => 1,
            'isenabled' => 1,
            'created' => '2026-01-01 00:00:00',
            'updated' => '2026-01-01 00:00:00',
        ], $overrides);
    }

    private function setupDocLookup(array $overrides = []): void
    {
        $row = $this->makeDocRow($overrides);
        DatabaseMock::setQueryResult(KB_DOCUMENTS_TABLE, [$row]);
    }

    public function testConstructorLoads(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals(1, $doc->getId());
    }

    public function testConstructorNotFound(): void
    {
        $doc = new Document(999);
        $this->assertEquals(0, $doc->getId());
    }

    public function testGetTitle(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('User Guide', $doc->getTitle());
    }

    public function testGetDescription(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('How to use the system', $doc->getDescription());
    }

    public function testGetDocType(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('file', $doc->getDocType());
    }

    public function testGetFileName(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('guide.pdf', $doc->getFileName());
    }

    public function testGetFileKey(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('abc123', $doc->getFileKey());
    }

    public function testGetFileSize(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals(1048576, $doc->getFileSize());
    }

    public function testGetAudience(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertEquals('staff', $doc->getAudience());
    }

    public function testIsEnabled(): void
    {
        $this->setupDocLookup(['isenabled' => 1]);
        $doc = new Document(1);
        $this->assertTrue($doc->isEnabled());
    }

    public function testIsDisabled(): void
    {
        $this->setupDocLookup(['isenabled' => 0]);
        $doc = new Document(1);
        $this->assertFalse($doc->isEnabled());
    }

    public function testIsFile(): void
    {
        $this->setupDocLookup(['doc_type' => 'file']);
        $doc = new Document(1);
        $this->assertTrue($doc->isFile());
        $this->assertFalse($doc->isLink());
    }

    public function testIsLink(): void
    {
        $this->setupDocLookup(['doc_type' => 'link']);
        $doc = new Document(1);
        $this->assertTrue($doc->isLink());
        $this->assertFalse($doc->isFile());
    }

    public function testGetAudienceLabelStaff(): void
    {
        $this->setupDocLookup(['audience' => 'staff']);
        $doc = new Document(1);
        $this->assertEquals('Менеджеры', $doc->getAudienceLabel());
    }

    public function testGetAudienceLabelClient(): void
    {
        $this->setupDocLookup(['audience' => 'client']);
        $doc = new Document(1);
        $this->assertEquals('Пользователи', $doc->getAudienceLabel());
    }

    public function testGetAudienceLabelAll(): void
    {
        $this->setupDocLookup(['audience' => 'all']);
        $doc = new Document(1);
        $this->assertEquals('Все', $doc->getAudienceLabel());
    }

    public function testGetDocTypeLabelFile(): void
    {
        $this->setupDocLookup(['doc_type' => 'file']);
        $doc = new Document(1);
        $this->assertEquals('Файл', $doc->getDocTypeLabel());
    }

    public function testGetDocTypeLabelLink(): void
    {
        $this->setupDocLookup(['doc_type' => 'link']);
        $doc = new Document(1);
        $this->assertEquals('Ссылка', $doc->getDocTypeLabel());
    }

    public function testGetFileSizeFormattedBytes(): void
    {
        $this->setupDocLookup(['file_size' => 500]);
        $doc = new Document(1);
        $this->assertEquals('500 Б', $doc->getFileSizeFormatted());
    }

    public function testGetFileSizeFormattedKilobytes(): void
    {
        $this->setupDocLookup(['file_size' => 5120]);
        $doc = new Document(1);
        $this->assertEquals('5 КБ', $doc->getFileSizeFormatted());
    }

    public function testGetFileSizeFormattedMegabytes(): void
    {
        $this->setupDocLookup(['file_size' => 2097152]);
        $doc = new Document(1);
        $this->assertEquals('2 МБ', $doc->getFileSizeFormatted());
    }

    public function testGetEmbedUrlGoogleDocs(): void
    {
        $this->setupDocLookup([
            'external_url' => 'https://docs.google.com/document/d/abc123xyz/edit',
        ]);
        $doc = new Document(1);
        $this->assertStringContainsString('preview', $doc->getEmbedUrl());
    }

    public function testGetEmbedUrlGoogleSheets(): void
    {
        $this->setupDocLookup([
            'external_url' => 'https://docs.google.com/spreadsheets/d/abc123xyz/edit',
        ]);
        $doc = new Document(1);
        $this->assertStringContainsString('pubhtml', $doc->getEmbedUrl());
    }

    public function testGetEmbedUrlGoogleSlides(): void
    {
        $this->setupDocLookup([
            'external_url' => 'https://docs.google.com/presentation/d/abc123xyz/edit',
        ]);
        $doc = new Document(1);
        $this->assertStringContainsString('embed', $doc->getEmbedUrl());
    }

    public function testGetEmbedUrlGeneric(): void
    {
        $this->setupDocLookup([
            'external_url' => 'https://example.com/doc.pdf',
        ]);
        $doc = new Document(1);
        $this->assertEquals('https://example.com/doc.pdf', $doc->getEmbedUrl());
    }

    public function testGetEmbedUrlEmpty(): void
    {
        $this->setupDocLookup(['external_url' => '']);
        $doc = new Document(1);
        $this->assertEquals('', $doc->getEmbedUrl());
    }

    public function testIsGoogleDoc(): void
    {
        $this->setupDocLookup([
            'external_url' => 'https://docs.google.com/document/d/abc/edit',
        ]);
        $doc = new Document(1);
        $this->assertTrue($doc->isGoogleDoc());
    }

    public function testIsNotGoogleDoc(): void
    {
        $this->setupDocLookup(['external_url' => 'https://example.com']);
        $doc = new Document(1);
        $this->assertFalse($doc->isGoogleDoc());
    }

    public function testIsNotGoogleDocEmpty(): void
    {
        $this->setupDocLookup(['external_url' => '']);
        $doc = new Document(1);
        $this->assertFalse($doc->isGoogleDoc());
    }

    public function testLookupFound(): void
    {
        $this->setupDocLookup();
        $doc = Document::lookup(1);
        $this->assertInstanceOf(Document::class, $doc);
    }

    public function testLookupNotFound(): void
    {
        $doc = Document::lookup(999);
        $this->assertNull($doc);
    }

    public function testCreateValidationNoTitle(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => '',
            'doc_type' => 'link',
            'external_url' => 'https://example.com',
            'audience' => 'staff',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testCreateValidationInvalidDocType(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => 'Test',
            'doc_type' => 'invalid',
            'audience' => 'staff',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('doc_type', $errors);
    }

    public function testCreateValidationLinkNoUrl(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => 'Test',
            'doc_type' => 'link',
            'external_url' => '',
            'audience' => 'staff',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('external_url', $errors);
    }

    public function testCreateValidationLinkInvalidUrl(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => 'Test',
            'doc_type' => 'link',
            'external_url' => 'not-a-url',
            'audience' => 'staff',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('external_url', $errors);
    }

    public function testCreateValidationInvalidAudience(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => 'Test',
            'doc_type' => 'link',
            'external_url' => 'https://example.com',
            'audience' => 'invalid',
            'staff_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('audience', $errors);
    }

    public function testCreateValidationNoStaffId(): void
    {
        $GLOBALS['cfg'] = null;
        $errors = [];
        $result = Document::create([
            'title' => 'Test',
            'doc_type' => 'link',
            'external_url' => 'https://example.com',
            'audience' => 'staff',
            'staff_id' => 0,
        ], $errors);
        $this->assertFalse($result);
    }

    public function testCreateLinkSuccessful(): void
    {
        $GLOBALS['cfg'] = null;
        DatabaseMock::setLastInsertId(5);
        $errors = [];
        $result = Document::create([
            'title' => 'Test Link',
            'description' => 'A test link',
            'doc_type' => 'link',
            'external_url' => 'https://example.com/doc',
            'audience' => 'all',
            'dept_id' => 0,
            'staff_id' => 1,
        ], $errors);
        $this->assertNotFalse($result);
    }

    public function testGetInfo(): void
    {
        $this->setupDocLookup();
        $doc = new Document(1);
        $this->assertIsArray($doc->getInfo());
    }
}
