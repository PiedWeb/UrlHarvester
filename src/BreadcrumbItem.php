<?php

namespace PiedWeb\UrlHarvester;

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
