<?php

namespace PiedWeb\UrlHarvester;

use phpUri;

/**
 * Quelques notes :
 * - Un bc ne contient pas l'élément courant.
 */
class ExtractBreadcrumb
{
    private $source;
    private $breadcrumbSource;
    private $breadcrumb = [];
    private $baseUrl;
    private $currentUrl;

    const BC_RGX = '#<(div|p|nav|ul)[^>]*(id|class)="?(breadcrumbs?|fil_?d?arian?ne)"?[^>]*>(.*)<\/(\1)>#siU';

    const BC_DIVIDER = [
        'class="?navigation-pipe"?',
        '&gt;',
        'class="?divider"?',
        '›',
        '</li>',
    ];

    /**
     * @param string $source  HTML code from the page
     * @param string $baseUrl To get absolute urls
     * @param string $current The current url. If not set we thing it's the same than $baseUrl
     *
     * @return array
     */
    public static function get(string $source, string $baseUrl, $current = null)
    {
        $self = new self();

        $self->source = $source;
        $self->baseUrl = $baseUrl;
        $self->currentUrl = null === $current ? $baseUrl : $current;

        return $self->extractBreadcrumb();
    }

    private function __construct()
    {
    }

    /**
     * @return array
     */
    public function extractBreadcrumb()
    {
        $breadcrumb = $this->findBreadcrumb();
        if (null !== $breadcrumb) {
            foreach (self::BC_DIVIDER as $divider) {
                $exploded = $this->divideBreadcrumb($breadcrumb, $divider);
                if (false !== $exploded) {
                    $this->extractBreadcrumbData($exploded);

                    return $this->breadcrumb;
                }
            }
        }
    }

    private function findBreadcrumb()
    {
        if (preg_match(self::BC_RGX, $this->source, $match)) {
            return $match[4];
        }
    }

    private function divideBreadcrumb($breadcrumb, $divider)
    {
        $exploded = preg_split('/'.str_replace('/', '\/', $divider).'/si', $breadcrumb);

        return count($exploded) > 1 ? $exploded : false;
    }

    /**
     * On essaye d'extraire l'url et l'ancre.
     */
    private function extractBreadcrumbData($array)
    {
        foreach ($array as $a) {
            $link = $this->extractHref($a);
            if (null === $link || $link == $this->currentUrl) {
                break;
            }
            $this->breadcrumb[] = new BreadcrumbItem(
                $link,
                $this->extractAnchor($a)
            );
        }
    }

    private function extractAnchor($str)
    {
        return trim(strtolower(Helper::htmlToPlainText($str)), '> ');
    }

    private function extractHref($str)
    {
        $regex = [
            'href="([^"]*)"',
            'href=\'([^\']*)\'',
            'href=(\S+) ',
        ];
        foreach ($regex as $r) {
            if (preg_match('/'.$r.'/siU', $str, $match)) {
                return phpUri::parse($this->baseUrl)->join($match[1]);
            }
        }
    }
}

class BreadcrumbItem
{
    private $url;
    private $name;

    public function __construct($url, $name)
    {
        $this->url = $url;
        $this->anchor = $name;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getName()
    {
        return $this->name;
    }
}
