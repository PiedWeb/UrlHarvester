<?php

declare(strict_types=1);

namespace PiedWeb\UrlHarvester\Test;

use PiedWeb\UrlHarvester\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testFailedRequest()
    {
        $request = Request::make('https://dzejnd'.rand(10000, 9999999999).'.biz', 'Hello :)');

        $this->assertSame(6, $request);
    }

    public function testRequest()
    {
        $request = Request::make(
            'https://piedweb.com/',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0'
        );

        $this->assertTrue(strlen($request->getContent()) > 10);
        $this->assertTrue(!empty($request->getHeaders()));
    }
}
