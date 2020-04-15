<?php

/**
 * Entity.
 */

namespace PiedWeb\UrlHarvester;

use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Manager;

class Domain
{
    protected static $rules;

    public static function resolve(string $host): \Pdp\Domain
    {
        return self::getRules()->resolve($host);
    }

    public static function getRegistrableDomain(string $host): ?string
    {
        return self::resolve($host)->getRegistrableDomain();
    }

    protected static function getRules()
    {
        if (null !== self::$rules) {
            return self::$rules;
        }

        return self::$rules = (new Manager(new Cache(), new CurlHttpClient()))->getRules();
    }
}
