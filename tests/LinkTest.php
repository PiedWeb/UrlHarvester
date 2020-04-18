<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Url;
use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use PiedWeb\UrlHarvester\Link;
use PiedWeb\Curl\ResponseFromCache;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use League\Uri\UriResolver;
use League\Uri\Uri;
use League\Uri\Http;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    private static $harvest;

    private function getUrl()
    {
        return 'https://piedweb.com/';
    }

    private function getHarvest()
    {
        if (null === self::$harvest) {
            self::$harvest = Harvest::fromUrl(
                $this->getUrl(),
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
            );
        }

        return self::$harvest;
    }

    public function testLinkNormalization()
    {

        $link = new Link('https://www.piedweb.com', $this->getHarvest());

        $this->assertSame($link->getPageUrl(), 'https://www.piedweb.com/');
        $this->assertSame($link->getUrl()->getRelativizedDocumentUrl(), '/');
    }

    private function getDomElement()
    {
         $html = '<a href="/test" rel=nofollow>test</a>';
        $dom = new DomCrawler($html);

        return $dom->filter('a')->getNode(0);
    }

    public function testNofollow()
    {
        $link = new Link('https://piedweb.com/test', $this->getHarvest(), $this->getDomElement());

        $this->assertTrue(! $link->mayFollow());
    }

    public function testAnchor()
    {
        $link = new Link('https://piedweb.com', $this->getHarvest(), $this->getDomElement());

        $this->assertSame($link->getAnchor(), 'test');
    }

    public function testShortcutsForRedir()
    {
        $link = Link::createRedirection('https://piedweb.com', $this->getHarvest());
        $this->assertTrue($link->mayFollow());
        $this->assertSame($link->getAnchor(), null);
    }

    public function testLinkType()
    {
        $link = new Link('https://external.com/test', $this->getHarvest(), $this->getDomElement());

        $this->assertTrue(!$link->isInternalLink());
        $this->assertTrue(!$link->isSubLink());
        $this->assertTrue(!$link->isSelfLink());
    }

    public function testUrl()
    {
        $url = new Url($this->getUrl());

        $this->assertTrue($url->resolve('//piedweb.com') == 'https://piedweb.com');
    }

    public function testRelativize()
    {
        $url = new Url($this->getUrl());

        $this->assertSame($url->relativize(), '/');
    }
}
