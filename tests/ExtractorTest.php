<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Request;
use PiedWeb\UrlHarvester\Helper;
use PiedWeb\UrlHarvester\ExtractLinks;
use PiedWeb\UrlHarvester\ExtractBreadcrumb;

class ExtractorTest extends \PHPUnit\Framework\TestCase
{
    private $dom;

    private function getUrl()
    {
        return 'https://piedweb.com/';
    }

    private function getDom(?string $url = null)
    {
        if ($this->dom === null) {
            $request = Request::make(
                $url !== null ? $url : $this->getUrl(),
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
            );

            $this->dom = new \simple_html_dom();
            $this->dom->load($request->getResponse()->getContent());
        }

        return $this->dom;
    }

    public function testExtractLinks()
    {
        $links = ExtractLinks::get($this->getDom(), $this->getUrl());

        foreach ($links as $link) {
            $this->assertTrue(strlen($link->getUrl())>10);
        }
    }

    public function testExtractAllLinks()
    {
        $links = ExtractLinks::get($this->getDom(), $this->getUrl(), ExtractLinks::SELECT_ALL);

        foreach ($links as $link) {
            $this->assertTrue(strlen($link->getUrl())>10);
        }
    }

    public function testExtractBreadcrumb()
    {
        $url = 'https://piedweb.com/a-propos';
        $dom = $this->getDom($url);
        $bcItems = ExtractBreadcrumb::get($dom->save(), 'https://piedweb.com/', $url);

        foreach ($bcItems as $item) {
            var_dump($item->getUrl());
            $this->assertTrue(strlen($item->getUrl())>10);
        }
    }

    public function testHelperClean()
    {
        $this->assertSame('Hello Toi', Helper::clean('Hello  Toi '));
    }
}
