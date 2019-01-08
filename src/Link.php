<?php

namespace PiedWeb\UrlHarvester;

class Link
{
    private $url;
    private $anchor;
    private $element;

    public function __construct(string $url, $dom)
    {
        $this->url = $url;
        $this->anchor = substr(Helper::clean($dom->innertext), 0, 100);
        $this->dom = $dom;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getAnchor()
    {
        return $this->anchor;
    }

    public function getElement()
    {
        $this->dom;
    }
}
