<?php

use PHPUnit\Framework\TestCase;

class TaskAttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        DatabaseMock::reset();
    }

    private function makeAttachmentRow(array $overrides = []): array
    {
        return array_merge([
            'attachment_id' => 1,
            'task_id' => 1,
            'file_name' => 'document.pdf',
            'file_key' => 'abc123def456',
            'file_size' => 51200,
            'file_mime' => 'application/pdf',
            'uploaded_by' => 1,
            'uploaded_date' => '2026-01-15 10:00:00',
            'uploader_name' => 'John Doe',
        ], $overrides);
    }

    private function setupAttachmentLookup(array $overrides = []): void
    {
        $row = $this->makeAttachmentRow($overrides);
        DatabaseMock::setQueryResult(TASK_ATTACHMENTS_TABLE, [$row]);
    }

    public function testConstructorLoadsAttachment(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals(1, $att->getId());
    }

    public function testConstructorNotFound(): void
    {
        $att = new TaskAttachment(999);
        $this->assertEquals(0, $att->getId());
    }

    public function testGetTaskId(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals(1, $att->getTaskId());
    }

    public function testGetFileName(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals('document.pdf', $att->getFileName());
    }

    public function testGetFileKey(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals('abc123def456', $att->getFileKey());
    }

    public function testGetFileSize(): void
    {
        $this->setupAttachmentLookup(['file_size' => 1024]);
        $att = new TaskAttachment(1);
        $this->assertEquals(1024, $att->getFileSize());
    }

    public function testGetFileMime(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals('application/pdf', $att->getFileMime());
    }

    public function testGetUploadedBy(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals(1, $att->getUploadedBy());
    }

    public function testGetUploaderName(): void
    {
        $this->setupAttachmentLookup();
        $att = new TaskAttachment(1);
        $this->assertEquals('John Doe', $att->getUploaderName());
    }

    public function testGetFileSizeFormattedBytes(): void
    {
        $this->setupAttachmentLookup(['file_size' => 500]);
        $att = new TaskAttachment(1);
        $this->assertEquals('500 Б', $att->getFileSizeFormatted());
    }

    public function testGetFileSizeFormattedKilobytes(): void
    {
        $this->setupAttachmentLookup(['file_size' => 5120]);
        $att = new TaskAttachment(1);
        $this->assertEquals('5 КБ', $att->getFileSizeFormatted());
    }

    public function testGetFileSizeFormattedMegabytes(): void
    {
        $this->setupAttachmentLookup(['file_size' => 2097152]);
        $att = new TaskAttachment(1);
        $this->assertEquals('2 МБ', $att->getFileSizeFormatted());
    }

    public function testIsImageJpeg(): void
    {
        $this->setupAttachmentLookup(['file_mime' => 'image/jpeg']);
        $att = new TaskAttachment(1);
        $this->assertTrue($att->isImage());
    }

    public function testIsImagePng(): void
    {
        $this->setupAttachmentLookup(['file_mime' => 'image/png']);
        $att = new TaskAttachment(1);
        $this->assertTrue($att->isImage());
    }

    public function testIsNotImagePdf(): void
    {
        $this->setupAttachmentLookup(['file_mime' => 'application/pdf']);
        $att = new TaskAttachment(1);
        $this->assertFalse($att->isImage());
    }

    public function testGetIconClassPdf(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'doc.pdf']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-pdf-o', $att->getIconClass());
    }

    public function testGetIconClassWord(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'doc.docx']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-word-o', $att->getIconClass());
    }

    public function testGetIconClassExcel(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'data.xlsx']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-excel-o', $att->getIconClass());
    }

    public function testGetIconClassArchive(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'archive.zip']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-archive-o', $att->getIconClass());
    }

    public function testGetIconClassImage(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'photo.jpg']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-image-o', $att->getIconClass());
    }

    public function testGetIconClassText(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'readme.txt']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-text-o', $att->getIconClass());
    }

    public function testGetIconClassUnknown(): void
    {
        $this->setupAttachmentLookup(['file_name' => 'file.xyz']);
        $att = new TaskAttachment(1);
        $this->assertEquals('file-o', $att->getIconClass());
    }

    public function testLookupFound(): void
    {
        $this->setupAttachmentLookup();
        $att = TaskAttachment::lookup(1);
        $this->assertInstanceOf(TaskAttachment::class, $att);
    }

    public function testLookupNotFound(): void
    {
        $att = TaskAttachment::lookup(999);
        $this->assertNull($att);
    }

    public function testGetByTaskId(): void
    {
        DatabaseMock::setQueryResult(TASK_ATTACHMENTS_TABLE, [
            $this->makeAttachmentRow(),
            $this->makeAttachmentRow(['attachment_id' => 2, 'file_name' => 'image.png']),
        ]);
        $attachments = TaskAttachment::getByTaskId(1);
        $this->assertIsArray($attachments);
        $this->assertCount(2, $attachments);
    }

    public function testGetCountByTaskId(): void
    {
        DatabaseMock::setQueryResult('SELECT COUNT(*)', [['COUNT(*)' => 3]]);
        $count = TaskAttachment::getCountByTaskId(1);
        $this->assertEquals(3, $count);
    }

    public function testUploadValidationNoTaskId(): void
    {
        $errors = [];
        $result = TaskAttachment::upload(0, ['error' => UPLOAD_ERR_OK], 1, $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('err', $errors);
    }

    public function testUploadValidationUploadError(): void
    {
        $errors = [];
        $result = TaskAttachment::upload(1, ['error' => UPLOAD_ERR_NO_FILE], 1, $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('file', $errors);
    }

    public function testDeleteByTaskId(): void
    {
        $GLOBALS['cfg'] = null;
        $result = TaskAttachment::deleteByTaskId(1);
        $this->assertTrue($result);
    }
}
