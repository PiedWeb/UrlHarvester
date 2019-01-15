<?php

namespace PiedWeb\UrlHarvester;

class Link
{
    private $url;
    private $anchor;
    private $element;

    public function __construct(string $url, $element)
    {
        $this->url = trim($url);
        $this->setAnchor($element);
        $this->element = $element;
    }

    protected function setAnchor($element)
    {
        $this->anchor = substr(Helper::clean($element->plaintext), 0, 100);

        if (empty($this->anchor) && $element->find('*[alt]', 0)) {
            $this->anchor = substr(Helper::clean($element->find('*[alt]', 0)->alt), 0, 100);
        }
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
        return $this->element;
    }
}
