<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use PiedWeb\UrlHarvester\Link;
use PiedWeb\Curl\ResponseFromCache;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class HarvestTest extends \PHPUnit\Framework\TestCase
{
    private static $harvest;

    private function getUrl()
    {
        return 'https://piedweb.com/seo/crawler';
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

    public function testHarvest()
    {
        $harvest = $this->getHarvest();
        $url = $this->getUrl();

        // Just check Curl  is doing is job
        $this->assertTrue($harvest->getResponse()->getInfo('total_time') > 0.00000001);
        $this->assertTrue(strlen($harvest->getTag('h1')) > 2);
        $this->assertTrue(strlen($harvest->getMeta('description')) > 2);
        $this->assertTrue('https://piedweb.com/seo/crawler' == $harvest->getCanonical());
        $this->assertTrue($harvest->isCanonicalCorrect());
        $this->assertTrue($harvest->getRatioTxtCode() > 2);
        $this->assertTrue(is_array($harvest->getKws()));

        $this->assertTrue(strlen($harvest->getUniqueTag('head title')) > 10);

        $this->assertTrue(is_array($harvest->getBreadCrumb()));

        $this->assertSame('piedweb.com', $harvest->getDomain());
        $this->assertSame('https://piedweb.com/seo/crawler', $harvest->getBaseUrl());
    }

    public function testHarvestLinks()
    {
        $harvest = $this->getHarvest();
        $url = $this->getUrl();

        $this->assertTrue(is_array($harvest->getLinkedRessources()));
        $this->assertTrue(is_array($harvest->getLinks()));
        $this->assertTrue(is_array($harvest->getLinks(Harvest::LINK_SELF)));
        $this->assertTrue(is_array($harvest->getLinks(Harvest::LINK_INTERNAL)));
        $this->assertTrue(is_array($harvest->getLinks(Harvest::LINK_SUB)));
        $this->assertTrue(is_array($harvest->getLinks(Harvest::LINK_EXTERNAL)));
        $this->assertTrue(is_int($harvest->getNbrDuplicateLinks()));
        $this->assertSame('/seo/crawler', $harvest->getAbsoluteInternalLink($this->getUrl()));
    }

    public function testFollow()
    {
        $harvest = $this->getHarvest();

        $this->assertTrue($harvest->getLinks()[0]->mayFollow());
        $this->assertTrue($harvest->mayFollow());
    }

    public function testNofollow()
    {
        $html = '<a href="/test" rel=nofollow>test</a>';
        $dom = new DomCrawler($html);

        $link = new Link('/test', $dom->filter('a')->getNode(0));

        $this->assertTrue(! $link->mayFollow());
    }

    public function testRedirection()
    {
        $url = 'https://www.piedweb.com/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame('https://piedweb.com/', $harvest->getRedirection());
        $this->assertSame(Indexable::NOT_INDEXABLE_3XX, $harvest->isIndexable());
    }

    public function testDomain()
    {
        $url = 'https://www.google.co.uk/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame('google.co.uk', $harvest->getDomain());
    }

    public function testIndexable()
    {
        $url = 'https://dev.piedweb.com/disallow';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame(Indexable::NOT_INDEXABLE_ROBOTS, $harvest->isIndexable());
        $harvest->setRobotsTxt($harvest->getRobotsTxt());

        $indexable = new Indexable($this->getHarvest());
        $this->assertTrue($indexable->metaAllows());
        $this->assertTrue($indexable->headersAllow());
    }

    public function testHarvestWithPreviousRequest()
    {
        $harvest = $this->getHarvest();

        $newHarvest = Harvest::fromUrl(
            $this->getUrl(),
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0',
            'fr',
            $harvest->getResponse()->getRequest()
        );

        $this->assertTrue(strlen($harvest->getUniqueTag('head title')) > 10);
    }

    public function testHarvestFromCache()
    {
        $harvest = new Harvest(new ResponseFromCache(
            'HTTP/1.1 200 OK'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));

        $this->assertSame(0, $harvest->isIndexable());
    }
}
