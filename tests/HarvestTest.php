<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\Curl\ResponseFromCache;
use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use PiedWeb\UrlHarvester\Link;

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

    private function getHarvestFromCache()
    {
        $response = new ResponseFromCache(
            'HTTP/1.1 200 OK'.PHP_EOL.PHP_EOL.file_get_contents(__DIR__.'/page.html'),
            $this->getUrl(),
            ['content_type' => 'text/html; charset=UTF-8']
        );

        $harvest = new Harvest($response);

        return $harvest;
    }

    public function testHarvestWordCount()
    {
        $harvest = $this->getHarvestFromCache();

        $this->assertTrue($harvest->getWordCount() > 50);
    }

    public function testHarvestLinksForReal()
    {
        $harvest = $this->getHarvestFromCache();
        $url = $this->getUrl();

        $this->assertTrue(count($harvest->getLinks(Link::LINK_SELF)) == 1);
        $this->assertTrue(count($harvest->getLinks(Link::LINK_INTERNAL)) == 4);
        $this->assertTrue(count($harvest->getLinks(Link::LINK_EXTERNAL)) == 3);
        $this->assertTrue(count($harvest->getLinks(Link::LINK_SUB)) == 2);
        $this->assertTrue(count($harvest->getLinks()) == 10);
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

        $this->assertTrue(null === $harvest->getUniqueTag('h12'));

        $this->assertTrue(is_array($harvest->getBreadCrumb()));

        $this->assertSame('piedweb.com', $harvest->url()->getRegistrableDomain());
        $this->assertSame('https://piedweb.com/seo/crawler', $harvest->getBaseUrl());
    }

    public function testHarvestLinks()
    {
        $harvest = $this->getHarvest();
        $url = $this->getUrl();


        $this->assertTrue(is_array($harvest->getLinkedRessources()));
        $this->assertTrue(is_array($harvest->getLinks()));
        $this->assertTrue(is_array($harvest->getLinks(Link::LINK_SELF)));
        $this->assertTrue(is_array($harvest->getLinks(Link::LINK_INTERNAL)));
        $this->assertTrue(is_array($harvest->getLinks(Link::LINK_SUB)));
        $this->assertTrue(is_array($harvest->getLinks(Link::LINK_EXTERNAL)));
        $this->assertTrue(is_int($harvest->getNbrDuplicateLinks()));
    }

    public function testFollow()
    {
        $harvest = $this->getHarvest();

        $this->assertTrue($harvest->getLinks()[0]->mayFollow());
        $this->assertTrue($harvest->mayFollow());
    }

    public function testRedirection()
    {
        $url = 'https://www.piedweb.com/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame('https://piedweb.com/', $harvest->getRedirection());
        $this->assertSame(Indexable::NOT_INDEXABLE_3XX, $harvest->indexable());
    }

    public function testIndexable()
    {
        $indexable = new Indexable($this->getHarvest());
        $this->assertTrue($indexable->metaAllows());
        $this->assertTrue($indexable->headersAllow());
        $this->assertTrue(Indexable::INDEXABLE == $this->getHarvest()->indexable());

        $url = 'https://dev.piedweb.com/disallow';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame(Indexable::NOT_INDEXABLE_ROBOTS, $harvest->indexable());
        $harvest->setRobotsTxt($harvest->getRobotsTxt());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 200 OK'.PHP_EOL.'X-robots-tag: noindex'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head></head><body><p>Tests</p></body>',
            'https://piedweb.com/noindex-headers',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_HEADER, $harvest2->indexable());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 200 OK'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head><meta name="robots" content="noindex"></head><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_META, $harvest2->indexable());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 200 OK'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head><link rel="canonical" href="https://piedweb.com/seo" /></head><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_CANONICAL, $harvest2->indexable());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 404 OK'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head></head><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_4XX, $harvest2->indexable());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 510 OK'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head></head><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_5XX, $harvest2->indexable());

        $harvest2 = new Harvest(new ResponseFromCache(
            'HTTP/1.1 301 Moved Permanently'.PHP_EOL.PHP_EOL.'<!DOCTYPE html><html>'
            .'<head></head><body><p>Tests</p></body>',
            'https://piedweb.com/',
            ['content_type' => 'text/html; charset=UTF-8']
        ));
        $harvest2->setRobotsTxt($harvest->getRobotsTxt());
        $this->assertSame(Indexable::NOT_INDEXABLE_3XX, $harvest2->indexable());
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

        $this->assertTrue($harvest->isIndexable());
    }

    public function testTextAnalysis()
    {
        $this->assertTrue(count($this->getHarvest()->getTextAnalysis()->getExpressionsByDensity()) > 1);
    }

    public function testCanonical()
    {
        $harvest = Harvest::fromUrl(
            'https://piedweb.com',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertTrue($harvest->isCanonicalCorrect('https://piedweb.com'));
        $this->assertTrue($harvest->isCanonicalCorrect('https://piedweb.com/'));
        $this->assertFalse($harvest->isCanonicalCorrect('https://piedweb.com//'));

    }
}
