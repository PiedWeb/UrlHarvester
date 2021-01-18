<?php

namespace PiedWeb\UrlHarvester;

class ExtractLinks
{
    const SELECT_A = 'a[href]';

    const SELECT_ALL = '[href],[src]';

    /** @var Harvest */
    private $harvest;

    /** @var string */
    private $selector;

    public static function get(Harvest $harvest, $selector = self::SELECT_A): array
    {
        $self = new self();

        $self->selector = $selector;
        $self->harvest = $harvest;

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
        $elements = $this->harvest->getDom()->filter($this->selector); // what happen if find nothing

        foreach ($elements as $element) {
            //var_dump(get_class_methods($element->getNode()));
            //if (!$element instanceof \DomElement) { continue; } // wtf ?
            $url = $this->extractUrl($element);
            //$type = $element->getAttribute('href') ? Link::LINK_A : Link::LINK_SRC;
            if (null !== $url) {
                //$links[] = (new Link($url, $element, $type))->setParent($this->parentUrl);
                $links[] = (new Link($url, $this->harvest, $element));
            }
        }

        return $links;
    }

    /**
     * @return string|null absolute url
     */
    private function extractUrl(\DomElement $element): ?string
    {
        $attributes = explode(',', str_replace(['a[', '*[', '[', ']'], '', $this->selector));
        foreach ($attributes as $attribute) {
            $url = $element->getAttribute($attribute);
            if ($url) {
                break;
            }
        }

        if (! $url || ! $this->isWebLink($url)) {
            return null;
        }

        return $this->harvest->url()->resolve($url);
    }

    public static function isWebLink(string $url)
    {
        return preg_match('@^((?:(http:|https:)//([\w\d-]+\.)+[\w\d-]+){0,1}(/?[\w~,;\-\./?%&+#=]*))$@', $url);
    }
}
