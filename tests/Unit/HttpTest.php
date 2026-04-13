<?php

use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Http')) {
            require_once INCLUDE_DIR . 'class.http.php';
        }
    }

    public function testHeaderCodeVerbose200(): void
    {
        $this->assertEquals('200 OK', Http::header_code_verbose(200));
    }

    public function testHeaderCodeVerbose204(): void
    {
        $this->assertEquals('204 NoContent', Http::header_code_verbose(204));
    }

    public function testHeaderCodeVerbose401(): void
    {
        $this->assertEquals('401 Unauthorized', Http::header_code_verbose(401));
    }

    public function testHeaderCodeVerbose403(): void
    {
        $this->assertEquals('403 Forbidden', Http::header_code_verbose(403));
    }

    public function testHeaderCodeVerbose405(): void
    {
        $this->assertEquals('405 Method Not Allowed', Http::header_code_verbose(405));
    }

    public function testHeaderCodeVerbose416(): void
    {
        $this->assertEquals('416 Requested Range Not Satisfiable', Http::header_code_verbose(416));
    }

    public function testHeaderCodeVerboseDefault(): void
    {
        $this->assertEquals('500 Internal Server Error', Http::header_code_verbose(999));
    }

    public function testHeaderCodeVerbose500(): void
    {
        $this->assertEquals('500 Internal Server Error', Http::header_code_verbose(500));
    }

    public function testHeaderCodeVerboseZero(): void
    {
        $this->assertEquals('500 Internal Server Error', Http::header_code_verbose(0));
    }
}
