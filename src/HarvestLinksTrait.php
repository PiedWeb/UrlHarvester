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
            case Harvest::LINK_SELF:
                return $this->linksPerType[Harvest::LINK_SELF] ?? [];
            case Harvest::LINK_INTERNAL:
                return $this->linksPerType[Harvest::LINK_INTERNAL] ?? [];
            case Harvest::LINK_SUB:
                return $this->linksPerType[Harvest::LINK_SUB] ?? [];
            case Harvest::LINK_EXTERNAL:
                return $this->linksPerType[Harvest::LINK_EXTERNAL] ?? [];
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

    abstract public function getDomainAndScheme();

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
        return 0 === strpos($url, $this->getDomainAndScheme());
    }

    public function isSubType(string $host)
    {
        return strtolower(substr($host, -strlen($this->getDomain()))) === $this->getDomain();
    }

    public function isSelfType(string $url)
    {
        if (0 !== strpos($url, '#')) {
            $url = substr($url, 0, -(strlen(parse_url($url, PHP_URL_FRAGMENT)) + 1));
        }

        return $this->isInternalType($url) && $url == $this->response->getEffectiveUrl();
    }

    public function getType(string $url): string
    {
        if ($this->isSelfType($url)) {
            return Harvest::LINK_SELF;
        }

        if ($this->isInternalType($url)) {
            return Harvest::LINK_INTERNAL;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host && $this->isSubType($host)) {
            return Harvest::LINK_SUB;
        }

        return Harvest::LINK_EXTERNAL;
    }

    public function getAbsoluteInternalLink(string $url)
    {
        return substr($url, strlen($this->getDomainAndScheme()));
    }
}
