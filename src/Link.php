<?php

/**
 * Entity.
 */

namespace PiedWeb\UrlHarvester;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Link
{
    private $url;
    private $anchor;
    private $element;

    public function __construct(string $url, \DOMElement $element = null)
    {
        $this->url = trim($url);
        if (null !== $element) {
            $this->setAnchor($element);
        }
        $this->element = $element;
    }

    protected function setAnchor(\DomElement $element)
    {
        // Get classic text anchor
        $this->anchor = $element->textContent;

        // If get nothing, then maybe we can get an alternative text (eg: img)
        if (empty($this->anchor)) {
            $alt = (new DomCrawler($element))->filter('*[alt]');
            if ($alt->count() > 0) {
                $this->anchor = $alt->eq(0)->attr('alt') ?? '';
            }
        }

        // Limit to 100 characters
        // Totally subjective
        $this->anchor = substr(Helper::clean($this->anchor), 0, 99);

        return $this;
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

    /**
     * @return bool
     */
    public function mayFollow()
    {
        if (null !== $this->element && null !== $this->element->getAttribute('rel')) {
            if (false !== strpos($this->element->getAttribute('rel'), 'nofollow')) {
                return false;
            }
        }

        return true;
    }
}
