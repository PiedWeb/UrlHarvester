<?php

namespace PiedWeb\UrlHarvester;

class Link
{
    private $url;
    private $anchor;
    private $element;

    public function __construct(string $url, $element)
    {
        $this->url = $url;
        $this->anchor = substr(Helper::clean($element->innertext), 0, 100);
        $this->element = $element;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getPageUrl()
    {
        return preg_replace('/(\#.*)/si', '', $this->url);
    }

    public function getAnchor()
    {
        return $this->anchor;
    }

    public function getElement()
    {
        $this->element;
    }
}
