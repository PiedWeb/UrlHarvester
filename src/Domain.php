<?php

/**
 * Entity.
 */

namespace PiedWeb\UrlHarvester;

use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Manager;
use Pdp\Rules;

class Domain
{
    protected static $rules;

    public static function resolve(string $host): \Pdp\ResolvedDomainName
    {
        return self::getRules()->resolve($host);
    }

    public static function getRegistrableDomain(string $host): ?string
    {
        return self::resolve($host)->registrableDomain()->toString();
    }

    protected static function getRules()
    {
        if (null !== self::$rules) {
            return self::$rules;
        }

        $reflector = new \ReflectionClass("Pdp\Rules");
        $base = \dirname($reflector->getFileName(), 2);

        return self::$rules = Rules::fromPath($base.'/test_data/public_suffix_list.dat');

        //return self::$rules = (new Manager(new Cache(), new CurlHttpClient()))->getRules();
    }
}
