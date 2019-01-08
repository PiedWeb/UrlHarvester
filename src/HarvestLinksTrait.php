<?php

namespace PiedWeb\UrlHarvester;

trait HarvestLinksTrait
{
    /**
     * @var array
     */
    protected $links;
    protected $selfs = [];
    protected $internals = [];
    protected $subs = [];
    protected $externals = [];

    private $domainWithScheme;

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
            case 'self':
                return $this->selfs;
            case 'internal':
                return $this->internals;
            case 'sub':
                return $this->subs;
            case 'external':
                return $this->externals;
            default:
                return $this->links;
        }
    }

    public function getDomainAndScheme()
    {
        if (null === $this->domainWithScheme) {
            $url = parse_url($this->response->getEffectiveUrl());
            $this->domainWithScheme = $url['scheme'].'://'.$url['host'];
        }

        return $this->domainWithScheme;
    }

    public function classifyLinks()
    {
        $links = $this->getLinks();

        foreach ($links as $link) {
            $urlParsed = parse_url($link->getUrl());

            if (preg_match('/^'.preg_quote($this->getDomainAndScheme().'/', '/').'/si', $link->getUrl().'/')) {
                if (preg_replace('/(\#.*)/si', '', $link->getUrl()) == $this->response->getEffectiveUrl()) {
                    $this->selfs[] = $link;
                } else {
                    $this->internals[] = $link;
                }
            } elseif (preg_match('/'.preg_quote($this->getDomain(), '/').'$/si', $urlParsed['host'])) {
                $this->subs[] = $link;
            } else {
                $this->externals[] = $link;
            }
        }
    }
}
