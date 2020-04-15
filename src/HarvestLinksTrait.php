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

    public function getLinkedRessources()
    {
        return ExtractLinks::get($this, ExtractLinks::SELECT_ALL);
    }

    public function getLinks($type = null): array
    {
        if (null === $this->links) {
            $this->links = ExtractLinks::get($this, ExtractLinks::SELECT_A);
            $this->classifyLinks();
        }

        switch ($type) {
            case Link::LINK_SELF:
                return $this->linksPerType[Link::LINK_SELF] ?? [];
            case Link::LINK_INTERNAL:
                return $this->linksPerType[Link::LINK_INTERNAL] ?? [];
            case Link::LINK_SUB:
                return $this->linksPerType[Link::LINK_SUB] ?? [];
            case Link::LINK_EXTERNAL:
                return $this->linksPerType[Link::LINK_EXTERNAL] ?? [];
            default:
                return $this->links;
        }
    }

    /**
     * Return duplicate links
     * /test and /test#2 are not duplicates.
     */
    public function getNbrDuplicateLinks(): int
    {
        $links = $this->getLinks();
        $u = [];
        foreach ($links as $link) {
            $u[(string) $link->getUrl()] = 1;
        }

        return count($links) - count($u);
    }

    public function classifyLinks()
    {
        $links = $this->getLinks();

        foreach ($links as $link) {
            $this->linksPerType[$link->getType()][] = $link;
        }
    }
}
