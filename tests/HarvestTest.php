<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Request;
use PiedWeb\UrlHarvester\Harvest;

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
        $this->assertTrue($harvest->getResponse()->getInfo('total_time') > 2);
        $this->assertTrue(strlen($harvest->getTag('h1')) > 2);
        $this->assertTrue(strlen($harvest->getMeta('description')) > 2);
        $this->assertTrue($harvest->getCanonical() == 'https://piedweb.com/a-propos');
        $this->assertTrue(!$harvest->isCanonicalCorrect());
        $this->assertTrue($harvest->getRatioTxtCode() > 2);
        $this->assertTrue(is_array($harvest->getKws()));

        $this->assertTrue(is_array($harvest->getBreadCrumb()));

        $this->assertSame('piedweb.com', $harvest->getDomain());
        $this->assertSame('https://www.piedweb.com/a-propos', $harvest->getBaseUrl());
    }

    function testHarvestLinks()
    {
        $url = 'https://piedweb.com/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertTrue(is_array($harvest->getLinks()));
        $this->assertTrue(is_array($harvest->getLinks('internal')));
        var_dump('internal');
        $this->debugLinks($harvest->getLinks('internal'));
        var_dump('self');
        $this->debugLinks($harvest->getLinks('self'));
        var_dump('sub');
        $this->debugLinks($harvest->getLinks('sub'));
        var_dump('external');
        $this->debugLinks($harvest->getLinks('external'));
        $this->assertTrue(is_array($harvest->getLinks('self')));
        $this->assertTrue(is_array($harvest->getLinks('sub')));
        $this->assertTrue(is_array($harvest->getLinks('external')));
    }

    function testDomain()
    {
        $url = 'https://www.google.co.uk/';
        $harvest = Harvest::fromUrl(
            $url,
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertSame('google.co.uk', $harvest->getDomain());
    }

    function debugLinks($links)
    {
        /**/
        foreach($links as $link) {
            var_dump($link->getUrl());
        }
        /**/
    }
}
