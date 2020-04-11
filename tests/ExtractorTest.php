<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Request;
use PiedWeb\UrlHarvester\Helper;
use PiedWeb\UrlHarvester\ExtractLinks;
use PiedWeb\UrlHarvester\ExtractBreadcrumb;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class ExtractorTest extends \PHPUnit\Framework\TestCase
{
    private static $dom;

    private function getUrl()
    {
        return 'https://piedweb.com/seo/crawler';
    }

    private function getDom()
    {
        if (null === self::$dom) {
            $request = Request::make(
                $this->getUrl(),
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
            );

            self::$dom = new DomCrawler($request->getContent());
        }

        return self::$dom;
    }

    public function testExtractLinks()
    {
        $links = ExtractLinks::get($this->getDom(), $this->getUrl());

        foreach ($links as $link) {
            $this->assertTrue(strlen($link->getUrl()) > 10);
        }
    }

    public function testExtractAllLinks()
    {
        $links = ExtractLinks::get($this->getDom(), $this->getUrl(), ExtractLinks::SELECT_ALL);

        foreach ($links as $link) {
            $this->assertTrue(strlen($link->getUrl()) > 10);
            break;
        }

        $links = ExtractLinks::get($this->getDom(), $this->getUrl(), ExtractLinks::SELECT_A);

        foreach ($links as $link) {
            $this->assertTrue(strlen($link->getUrl()) > 10);
            $this->assertTrue(strlen($link->getAnchor()) > 1);
            $this->assertTrue(strlen($link->getElement()->getAttribute('href')) >= 1);
            break;
        }
    }

    public function testExtractBreadcrumb()
    {
        $dom = $this->getDom();
        $bcItems = ExtractBreadcrumb::get($dom->html(), 'https://piedweb.com/', $this->getUrl());

        foreach ($bcItems as $item) {
            $this->assertTrue(strlen($item->getUrl()) > 10);
            $this->assertTrue(strlen($item->getName()) > 1);
            $this->assertTrue(strlen($item->getCleanName()) > 1);
        }
    }

    public function testHelperClean()
    {
        $this->assertSame('Hello Toi', Helper::clean('Hello  Toi '));
    }
}
