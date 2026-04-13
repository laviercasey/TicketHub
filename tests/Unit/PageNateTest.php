<?php

use PHPUnit\Framework\TestCase;

class PageNateTest extends TestCase
{
    public function testConstructorCalculatesPages(): void
    {
        $pager = new PageNate(100, 1, 20);
        $this->assertEquals(5, $pager->getNumPages());
    }

    public function testConstructorSetsStart(): void
    {
        $pager = new PageNate(100, 3, 20);
        $this->assertEquals(40, $pager->getStart());
    }

    public function testConstructorFirstPage(): void
    {
        $pager = new PageNate(100, 1, 20);
        $this->assertEquals(0, $pager->getStart());
    }

    public function testConstructorResetsStartWhenLimitExceedsTotal(): void
    {
        $pager = new PageNate(10, 1, 50);
        $this->assertEquals(0, $pager->getStart());
    }

    public function testConstructorResetsStartWhenPageExceedsTotalPages(): void
    {
        $pager = new PageNate(100, 10, 20);
        $this->assertEquals(0, $pager->getStart());
    }

    public function testConstructorMinimumLimitIsOne(): void
    {
        $pager = new PageNate(10, 1, 0);
        $this->assertEquals(1, $pager->getLimit());
    }

    public function testConstructorMinimumPageIsOne(): void
    {
        $pager = new PageNate(100, 0, 20);
        $this->assertEquals(1, $pager->page);
    }

    public function testGetStart(): void
    {
        $pager = new PageNate(100, 2, 20);
        $this->assertEquals(20, $pager->getStart());
    }

    public function testGetLimit(): void
    {
        $pager = new PageNate(100, 1, 25);
        $this->assertEquals(25, $pager->getLimit());
    }

    public function testGetNumPages(): void
    {
        $pager = new PageNate(50, 1, 10);
        $this->assertEquals(5, $pager->getNumPages());
    }

    public function testGetNumPagesRoundsUp(): void
    {
        $pager = new PageNate(51, 1, 10);
        $this->assertEquals(6, $pager->getNumPages());
    }

    public function testGetPage(): void
    {
        $pager = new PageNate(100, 2, 20);
        $this->assertEquals(2, $pager->getPage());
    }

    public function testGetPageFirstPage(): void
    {
        $pager = new PageNate(100, 1, 20);
        $this->assertEquals(1, $pager->getPage());
    }

    public function testShowingWithResults(): void
    {
        $pager = new PageNate(100, 1, 20);
        $showing = $pager->showing();
        $this->assertStringContainsString('1 - 20', $showing);
        $this->assertStringContainsString('100', $showing);
        $this->assertStringContainsString('Показано', $showing);
    }

    public function testShowingLastPage(): void
    {
        $pager = new PageNate(50, 3, 20);
        $showing = $pager->showing();
        $this->assertStringContainsString('50', $showing);
    }

    public function testShowingZeroResults(): void
    {
        $pager = new PageNate(0, 1, 20);
        $showing = $pager->showing();
        $this->assertStringContainsString('0', $showing);
    }

    public function testGetPageLinksReturnsHtml(): void
    {
        $pager = new PageNate(100, 1, 10, '/test?');
        $links = $pager->getPageLinks();
        $this->assertStringContainsString('<b>[1]</b>', $links);
        $this->assertStringContainsString('<a href=', $links);
    }

    public function testGetPageLinksCurrentPageIsBold(): void
    {
        $pager = new PageNate(100, 3, 10, '/test?');
        $links = $pager->getPageLinks();
        $this->assertStringContainsString('<b>[3]</b>', $links);
    }

    public function testGetPageLinksEmptyForSinglePage(): void
    {
        $pager = new PageNate(5, 1, 20, '/test?');
        $links = $pager->getPageLinks();
        $this->assertStringContainsString('<b>[1]</b>', $links);
        $this->assertStringNotContainsString('&raquo;', $links);
    }

    public function testSetURL(): void
    {
        $pager = new PageNate(100, 1, 20, '/test');
        $this->assertEquals('/test?', $pager->url);
    }

    public function testSetURLWithExistingQueryString(): void
    {
        $pager = new PageNate(100, 1, 20, '/test?foo=bar');
        $this->assertEquals('/test?foo=bar', $pager->url);
    }

    public function testDefaultLimitIsTwenty(): void
    {
        $pager = new PageNate(100, 1);
        $this->assertEquals(20, $pager->getLimit());
    }

    public function testTotalIsInteger(): void
    {
        $pager = new PageNate('abc', 1, 20);
        $this->assertEquals(0, $pager->total);
    }
}
