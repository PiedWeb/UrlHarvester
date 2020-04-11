<?php

namespace PiedWeb\UrlHarvester;

use phpUri;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class ExtractLinks
{
    const SELECT_A = 'a[href]';

    const SELECT_ALL = 'href,src';

    /**
     * @var \simple_html_dom
     */
    private $dom;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $selector;

    /**
     * @param string $dom      HTML code from the page
     * @param string $baseUrl  To get absolute urls
     * @param string $selector
     *
     * @return array
     */
    public static function get(DomCrawler $dom, string $baseUrl, $selector = self::SELECT_A)
    {
        $self = new self();

        $self->selector = $selector;
        $self->baseUrl = $baseUrl;
        $self->dom = $dom;

        return $self->extractLinks();
    }

    private function __construct()
    {
    }

    /**
     * @return array
     */
    private function extractLinks()
    {
        $links = [];
        $elements = $this->getElements();

        if ($elements) {
            foreach ($elements as $element) {
                $href = $this->getUrl($element);

                if (null !== $href) {
                    $links[] = new Link($href, $element);
                }
            }
        }

        return $links;
    }

    private function getElements()
    {
        if (self::SELECT_A == $this->selector) {
            return $this->dom->filter($this->selector);
        } else {
            return $this->dom->filter('['.implode('],*[', explode(',', $this->selector)).']');
        }
    }

    /**
     * @return string|null
     */
    private function getUrl(\DomElement $element)
    {
        if (self::SELECT_A == $this->selector) {
            $href = $element->getAttribute('href');
        } else {
            $attributes = explode(',', $this->selector);
            foreach ($attributes as $attribute) {
                $href = $element->getAttribute($attribute);
                if (null !== $href) {
                    break;
                }
            }
        }

        $href = $this->isItALink($href) ? $href : null;
        $parsed = phpUri::parse($this->baseUrl)->join($href);
        $href = null !== $href ? ($parsed ? $parsed : $href) : null;

        return $href;
    }

    private function isItALink(string $href)
    {
        return preg_match('@^(((http|https|ftp)://([\w\d-]+\.)+[\w\d-]+){0,1}(/?[\w~,;\-\./?%&+#=]*))$@', $href);
    }
}
