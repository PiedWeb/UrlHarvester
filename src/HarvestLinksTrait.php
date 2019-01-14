<?php

namespace PiedWeb\UrlHarvester;

trait HarvestLinksTrait
{

    /**
     * @var array
     */
    protected $links;
    protected $linksPerType;

    abstract public function getDom();

    abstract public function getDomain();

    public function getLinkedRessources()
    {
        return ExtractLinks::get($this->getDom(), $this->response->getEffectiveUrl(), ExtractLinks::SELECT_ALL);
    }

    public function getLinks($type = null)
    {
        if (null === $this->links) {
            $this->links = ExtractLinks::get($this->getDom(), $this->response->getEffectiveUrl());
            $this->classifyLinks();
        }

        switch ($type) {
            case self::LINK_SELF:
                return $this->linksPerType[self::LINK_SELF];
            case self::LINK_INTERNAL:
                return $this->linksPerType[self::LINK_INTERNAL];
            case self::LINK_SUB:
                return $this->linksPerType[self::LINK_SUB];
            case self::LINK_EXTERNAL:
                return $this->linksPerType[self::LINK_EXTERNAL];
            default:
                return $this->links;
        }
    }

    public function getNbrDuplicateLinks()
    {
        $links = $this->getLinks();
        $u = [];
        foreach ($links as $link) {
            $u[$link->getUrl()] = 1;
        }

        return count($links) - count($u);
    }

    abstract function getDomainAndScheme();

    public function classifyLinks()
    {
        $links = $this->getLinks();

        foreach ($links as $link) {
            $type = $this->getType($link->getPageUrl());
            $this->linksPerType[$type][] = $link;
        }
    }

    public function isInternalType(string $url)
    {
        return strpos($url, $this->getDomainAndScheme()) === 0;
    }

    public function isSubType(string $host)
    {
        return strtolower(substr($host, -strlen($this->getDomain()))) === $this->getDomain();
    }

    public function isSelfType(string $url)
    {
        if (strpos($url, '#') !== 0) {
            $url = substr($url, 0, -(strlen(parse_url($url, PHP_URL_FRAGMENT)) + 1));
        }

        return $this->isInternalType($url) && $url == $this->response->getEffectiveUrl();
    }

    public function getType(string $url): string
    {
        if ($this->isSelfType($url)) {
            return self::LINK_SELF;
        } elseif ($this->isInternalType($url)) {
            return self::LINK_INTERNAL;
        } elseif ($this->isSubType(parse_url($url, PHP_URL_HOST))) {
            return self::LINK_SUB;
        }

        return self::LINK_EXTERNAL;
    }

    public function getAbsoluteInternalLink(string $url)
    {
        return substr($url, strlen($this->getDomainAndScheme()));
    }
}
