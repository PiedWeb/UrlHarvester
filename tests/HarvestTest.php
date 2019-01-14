<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use  Spatie\Robots\RobotsTxt;

class HarvestTest extends \PHPUnit\Framework\TestCase
{
    public function testHarvest()
    {
        $url = 'https://www.piedweb.com/a-propos';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        // Just check Curl  is doing is job
        $this->assertTrue($harvest->getResponse()->getInfo('total_time') > 0.00000001);
        $this->assertTrue(strlen($harvest->getTag('h1')) > 2);
        $this->assertTrue(strlen($harvest->getMeta('description')) > 2);
        $this->assertTrue('https://piedweb.com/a-propos' == $harvest->getCanonical());
        $this->assertTrue(!$harvest->isCanonicalCorrect());
        $this->assertTrue($harvest->getRatioTxtCode() > 2);
        $this->assertTrue(is_array($harvest->getKws()));

        $this->assertTrue(strlen($harvest->getUniqueTag('head title')) > 10);

        $this->assertTrue(is_array($harvest->getBreadCrumb()));

        $this->assertSame('piedweb.com', $harvest->getDomain());
        $this->assertSame('https://www.piedweb.com/a-propos', $harvest->getBaseUrl());
    }

    public function testHarvestLinks()
    {
        $url = 'https://piedweb.com/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertTrue(is_array($harvest->getLinks()));
        $this->assertTrue(is_array($harvest->getLinks('internal')));
        $this->assertTrue(is_array($harvest->getLinks('self')));
        $this->assertTrue(is_array($harvest->getLinks('sub')));
        $this->assertTrue(is_array($harvest->getLinks('external')));
    }

    public function testRedirection()
    {
        $url = 'https://www.piedweb.com/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame('https://piedweb.com/', $harvest->getRedirection());
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
    }
}
