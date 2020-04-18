<?php

/**
 * Wrapper for League\Uri.
 *
 * Permits to cache registrableDomain and Origin
 */

namespace PiedWeb\UrlHarvester;

use League\Uri\Http;
use League\Uri\UriInfo;
use League\Uri\UriResolver;

class Url
{
    protected $http;
    protected $origin;
    protected $registrableDomain;

    public function __construct(string $url)
    {
        $this->http = Http::createFromString($url);

        if (!UriInfo::isAbsolute($this->http)) {
            throw new \Exception('$url must be absolute (`'.$url.'`)');
        }
    }

    public function resolve($url): string
    {
        $resolved = UriResolver::resolve(Http::createFromString($url), $this->http);

        return $resolved->__toString();
    }

    public function getHttp()
    {
        return $this->http;
    }

    public function getScheme()
    {
        return $this->http->getScheme();
    }

    public function getHost()
    {
        return $this->http->getHost();
    }

    public function getOrigin()
    {
        $this->origin = $this->origin ?? $this->origin = UriInfo::getOrigin($this->http);

        return $this->origin;
    }

    public function getRegistrableDomain()
    {
        return $this->registrableDomain
            ?? ($this->registrableDomain = Domain::getRegistrableDomain($this->http->getHost()));
    }

    public function getDocumentUrl(): string
    {
        return $this->http->withFragment('');
    }

    public function getRelativizedDocumentUrl(): string
    {
        return substr($this->http->withFragment(''), strlen($this->getOrigin()));
    }

    public function get()
    {
        return $this->__toString();
    }

    public function __toString()
    {
        return (string) $this->http;
    }

    public function relativize()
    {
        return substr($this->get(), strlen($this->getOrigin()));
    }
}
