<?php

use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function testFileSizeBytes(): void
    {
        $this->assertEquals('0 bytes', Format::file_size(0));
        $this->assertEquals('500 bytes', Format::file_size(500));
        $this->assertEquals('1023 bytes', Format::file_size(1023));
    }

    public function testFileSizeKilobytes(): void
    {
        $this->assertEquals('1 kb', Format::file_size(1024));
        $this->assertEquals('50 kb', Format::file_size(51200));
    }

    public function testFileSizeMegabytes(): void
    {
        $this->assertEquals('1 mb', Format::file_size(1024000));
        $this->assertEquals('5.1 mb', Format::file_size(5222400));
    }

    public function testFileName(): void
    {
        $this->assertEquals('test_file.txt', Format::file_name('test file.txt'));
        $this->assertEquals('Ueber_uns.pdf', Format::file_name('Über uns.pdf'));
        $this->assertEquals('file__name', Format::file_name('file @name'));
    }

    public function testFileNameGermanCharacters(): void
    {
        $this->assertStringContainsString('ae', Format::file_name('ä'));
        $this->assertStringContainsString('oe', Format::file_name('ö'));
        $this->assertStringContainsString('ue', Format::file_name('ü'));
        $this->assertStringContainsString('Ae', Format::file_name('Ä'));
        $this->assertStringContainsString('Oe', Format::file_name('Ö'));
        $this->assertStringContainsString('Ue', Format::file_name('Ü'));
        $this->assertStringContainsString('ss', Format::file_name('ß'));
    }

    public function testPhoneFormatting7Digits(): void
    {
        $this->assertEquals('123-4567', Format::phone('1234567'));
    }

    public function testPhoneFormatting10Digits(): void
    {
        $this->assertEquals('(123) 456-7890', Format::phone('1234567890'));
    }

    public function testPhoneFormattingOtherLengths(): void
    {
        $this->assertEquals('+7 999 123 45 67', Format::phone('+7 999 123 45 67'));
    }

    public function testTruncateShortString(): void
    {
        $this->assertEquals('hello', Format::truncate('hello', 10));
    }

    public function testTruncateWithWordBoundary(): void
    {
        $result = Format::truncate('hello beautiful world', 15);
        $this->assertStringEndsWith('...', $result);
        $this->assertLessThanOrEqual(18, strlen($result));
    }

    public function testTruncateHardCut(): void
    {
        $result = Format::truncate('hello beautiful world', 10, true);
        $this->assertEquals('hello beau', $result);
    }

    public function testTruncateZeroLen(): void
    {
        $this->assertEquals('test', Format::truncate('test', 0));
    }

    public function testTruncateNullString(): void
    {
        $this->assertNull(Format::truncate(null, 10));
    }

    public function testHtmlcharsEscapesSpecialChars(): void
    {
        $this->assertEquals('&lt;script&gt;', Format::htmlchars('<script>'));
        $this->assertEquals('&quot;test&quot;', Format::htmlchars('"test"'));
        $this->assertEquals('&#039;single&#039;', Format::htmlchars("'single'"));
        $this->assertEquals('a &amp; b', Format::htmlchars('a & b'));
    }

    public function testHtmlcharsWithArray(): void
    {
        $result = Format::htmlchars(['<b>bold</b>', '"quoted"']);
        $this->assertEquals(['&lt;b&gt;bold&lt;/b&gt;', '&quot;quoted&quot;'], $result);
    }

    public function testHtmlcharsWithNull(): void
    {
        $this->assertEquals('', Format::htmlchars(null));
    }

    public function testInputDelegatesToHtmlchars(): void
    {
        $this->assertEquals(Format::htmlchars('<test>'), Format::input('<test>'));
    }

    public function testStriptagsRemovesAllTags(): void
    {
        $this->assertEquals('hello world', Format::striptags('<p>hello <b>world</b></p>'));
        $this->assertEquals('test', Format::striptags('<script>test</script>'));
    }

    public function testStriptagsDecodesEntities(): void
    {
        $this->assertEquals('bold', Format::striptags('&lt;b&gt;bold&lt;/b&gt;'));
    }

    public function testClickableurlsConvertsHttpUrls(): void
    {
        $text = 'Visit https://example.com for more';
        $result = Format::clickableurls($text);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function testClickableurlsConvertsWwwUrls(): void
    {
        $text = 'Visit www.example.com for more';
        $result = Format::clickableurls($text);
        $this->assertStringContainsString('href="http://www.example.com"', $result);
    }

    public function testClickableurlsConvertsEmailAddresses(): void
    {
        $text = 'Contact admin@example.com for help';
        $result = Format::clickableurls($text);
        $this->assertStringContainsString('mailto:admin@example.com', $result);
    }

    public function testStripEmptyLines(): void
    {
        $input = "line1\n\n\n\n\nline2";
        $this->assertEquals("line1\n\nline2", Format::stripEmptyLines($input));
    }

    public function testLinebreaks(): void
    {
        $input = "line1\r\nline2";
        $result = Format::linebreaks($input);
        $this->assertStringNotContainsString("\r", $result);
    }

    public function testElapsedTimeDays(): void
    {
        $this->assertStringContainsString('1d,', Format::elapsedTime(86400));
    }

    public function testElapsedTimeHours(): void
    {
        $this->assertStringContainsString('2h,', Format::elapsedTime(7200));
    }

    public function testElapsedTimeMinutes(): void
    {
        $this->assertEquals('5m', Format::elapsedTime(300));
    }

    public function testElapsedTimeEmpty(): void
    {
        $this->assertEquals('', Format::elapsedTime(0));
        $this->assertEquals('', Format::elapsedTime(''));
    }

    public function testElapsedTimeCombined(): void
    {
        $result = Format::elapsedTime(90061);
        $this->assertStringContainsString('1d,', $result);
        $this->assertStringContainsString('1h,', $result);
        $this->assertStringContainsString('1m', $result);
    }

    public function testStripSlashesString(): void
    {
        $this->assertEquals("it's a test", Format::strip_slashes("it\\'s a test"));
    }

    public function testStripSlashesArray(): void
    {
        $input = ["it\\'s", "a\\\"test"];
        $result = Format::strip_slashes($input);
        $this->assertEquals(["it's", 'a"test'], $result);
    }

    public function testDateWithValidTimestamp(): void
    {
        $timestamp = mktime(12, 0, 0, 6, 15, 2026);
        $result = Format::date('Y-m-d', $timestamp);
        $this->assertEquals('2026-06-15', $result);
    }

    public function testDateWithOffset(): void
    {
        $timestamp = mktime(12, 0, 0, 6, 15, 2026);
        $result = Format::date('H', $timestamp, 3);
        $this->assertEquals('15', $result);
    }

    public function testDateWithZeroTimestamp(): void
    {
        $this->assertEquals('', Format::date('Y-m-d', 0));
    }

    public function testDateWithNonNumeric(): void
    {
        $this->assertEquals('', Format::date('Y-m-d', 'not-a-timestamp'));
    }

    public function testDisplayEscapesHtml(): void
    {
        $GLOBALS['cfg'] = null;
        $result = Format::display('<b>test</b>');
        $this->assertStringContainsString('&lt;b&gt;', $result);
    }
}
