<?php

/**
 * Entity.
 */

namespace PiedWeb\UrlHarvester;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Link
{
    /** @var Url */
    protected $url;

    /** @var string cache */
    protected $anchor;

    /** @var \DomElement */
    protected $element;

    /** @var Harvest */
    protected $parentDoc;

    /** @var int */
    protected $wrapper;

    /** @var int */
    protected $type;

    // Ce serait dans une liste, dans une phrase...
    protected $context;

    // wrapper related
    const LINK_A = 1;
    const LINK_SRC = 4;
    const LINK_3XX = 2;
    const LINK_301 = 3;

    // type related
    const LINK_SELF = 1;
    const LINK_INTERNAL = 2;
    const LINK_SUB = 3;
    const LINK_EXTERNAL = 4;

    /**
     * Add trailing slash for domain. Eg: https://piedweb.com => https://piedweb.com/ and '/test ' = '/test'.
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ('' == preg_replace('@(.*\://?([^\/]+))@', '', $url)) {
            $url .= '/';
        }

        return $url;
    }

    protected static function getWrapperFromElement(\DomElement $element): ?int
    {
        if ('a' == $element->tagName && $element->getAttribute('href')) {
            return self::LINK_A;
        }

        if ($element->getAttribute('src')) {
            return self::LINK_SRC;
        }

        return null;
    }

    /**
     * Always submit absoute Url !
     */
    public function __construct(string $url, Harvest $parent, \DOMElement $element = null, int $wrapper = null)
    {
        $this->url = new Url(self::normalizeUrl($url));

        $this->parentDoc = $parent;

        if (null !== $element) {
            $this->setAnchor($element);
        }

        $this->element = $element;

        $this->wrapper = $wrapper ?? (null !== $element ? self::getWrapperFromElement($element) : null);
    }

    public static function createRedirection(string $url, Harvest $parent, int $redirType = null): self
    {
        return new self($url, $parent, null, $redirType ?? self::LINK_3XX);
    }

    public function getWrapper(): ?int
    {
        return $this->wrapper;
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

    public function getUrl($string = false): Url
    {
        return $this->url;
    }

    public function getPageUrl(): string
    {
        return $this->url->getDocumentUrl(); //return preg_replace('/(\#.*)/si', '', $this->url->get());
    }

    public function getParentUrl(): Url
    {
        return $this->parentDoc->getUrl();
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
        // check meta robots and headers
        if (null !== $this->parentDoc && !$this->parentDoc->mayFollow()) {
            return false;
        }

        // check "wrapper" rel
        if (null !== $this->element && null !== $this->element->getAttribute('rel')) {
            if (false !== strpos($this->element->getAttribute('rel'), 'nofollow')
                || false !== strpos($this->element->getAttribute('rel'), 'sponsored')
                || false !== strpos($this->element->getAttribute('rel'), 'ugc')
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getRelAttribute(): ?string
    {
        return null !== $this->element ? $this->element->getAttribute('rel') : null;
    }

    public function isInternalLink(): bool
    {
        return $this->url->getOrigin() == $this->getParentUrl()->getOrigin();
    }

    public function isSubLink(): bool
    {
        return !$this->isInternalLink()
            && $this->url->getRegistrableDomain() == $this->getParentUrl()->getRegistrableDomain();
        //&& strtolower(substr($this->getHost(), -strlen($this->parentDomain))) === $this->parentDomain;
    }

    public function isSelfLink(): bool
    {
        return $this->isInternalLink()
            && $this->url->getDocumentUrl() == $this->getParentUrl()->getDocumentUrl();
    }

    public function getType()
    {
        if ($this->isSelfLink()) {
            return self::LINK_SELF;
        }

        if ($this->isInternalLink()) {
            return self::LINK_INTERNAL;
        }

        if ($this->isSubLink()) {
            return self::LINK_SUB;
        }

        return self::LINK_EXTERNAL;
    }

    // todo useless ?!
    public function getAbsoluteInternalLink()
    {
        if ($this->isInternalLink()) {
            return substr($this->url, strlen($this->getParentUrl()->getOrigin()));
        }
    }
}
