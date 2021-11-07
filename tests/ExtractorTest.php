<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\ExtractBreadcrumb;
use PiedWeb\UrlHarvester\ExtractLinks;
use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Helper;

class ExtractorTest extends \PHPUnit\Framework\TestCase
{
    private static $dom;

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

    public function testExtractLinks()
    {
        $links = ExtractLinks::get($this->getHarvest());

        foreach ($links as $link) {
            $this->assertTrue(\strlen($link->getUrl()->get()) > 10);
            $this->assertTrue(\strlen($link->getAnchor()) > 1);
            $this->assertTrue('' !== $link->getElement()->getAttribute('href'));
        }

        $links = ExtractLinks::get($this->getHarvest(), ExtractLinks::SELECT_ALL);
        foreach ($links as $link) {
            $this->assertTrue(\strlen($link->getUrl()->get()) > 10);
        }
    }

    public function testExtractBreadcrumb()
    {
        $bcItems = ExtractBreadcrumb::get($this->getHarvest());

        foreach ($bcItems as $item) {
            $this->assertTrue(\strlen($item->getUrl()) > 10);
            $this->assertTrue(\strlen($item->getName()) > 1);
            $this->assertTrue(\strlen($item->getCleanName()) > 1);
        }
    }

    public function testHelperClean()
    {
        $this->assertSame('Hello Toi', Helper::clean('Hello  Toi '));
    }
}
