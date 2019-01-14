<?php

namespace PiedWeb\UrlHarvester;

class BreadcrumbItem
{
    private $url;
    private $name;

    public function __construct($url, $name)
    {
        $this->url = $url;
        $this->name = $name;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCleanName()
    {
        return substr(strip_tags($this->name), 0, 100);
    }
}
