<?php

namespace PiedWeb\UrlHarvester;

use simple_html_dom_node;

class Link
{
    private $url;
    private $anchor;
    private $element;

    public function __construct(string $url, ?simple_html_dom_node $element = null)
    {
        $this->url = trim($url);
        if (null !== $element) {
            $this->setAnchor($element);
        }
        $this->element = $element;
    }

    protected function setAnchor(simple_html_dom_node $element)
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

    public function mayFollow()
    {
        if (isset($this->element) && isset($this->element->rel)) {
            if (false !== strpos($this->element->rel, 'nofollow')) {
                return false;
            }
        }

        return true;
    }
}
