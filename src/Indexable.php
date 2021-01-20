<?php

namespace PiedWeb\UrlHarvester;

use Spatie\Robots\RobotsHeaders;

class Indexable
{
    // https://stackoverflow.com/questions/1880148/how-to-get-name-of-the-constant
    public const INDEXABLE = 0;
    public const NOT_INDEXABLE_ROBOTS = 1;
    public const NOT_INDEXABLE_HEADER = 2;
    public const NOT_INDEXABLE_META = 3;
    public const NOT_INDEXABLE_CANONICAL = 4;
    public const NOT_INDEXABLE_4XX = 5;
    public const NOT_INDEXABLE_5XX = 6;
    public const NOT_INDEXABLE_NETWORK_ERROR = 7;
    public const NOT_INDEXABLE_TOO_BIG = 10;
    public const NOT_INDEXABLE_3XX = 8;
    public const NOT_INDEXABLE_NOT_HTML = 9;

    /** @var Harvest */
    protected $harvest;

    /** @var string */
    protected $isIndexableFor;

    public function __construct(Harvest $harvest, string $isIndexableFor = 'googlebot')
    {
        $this->harvest = $harvest;
        $this->isIndexableFor = $isIndexableFor;
    }

    public function robotsTxtAllows()
    {
        $url = $this->harvest->getResponse()->getUrl();
        $robotsTxt = $this->harvest->getRobotsTxt();

        return '' === $robotsTxt ? true : $robotsTxt->allows($url, $this->isIndexableFor);
    }

    public function metaAllows()
    {
        $meta = $this->harvest->getMeta($this->isIndexableFor);
        $generic = $this->harvest->getMeta('robots');

        return ! (false !== stripos($meta, 'noindex') || false !== stripos($generic, 'noindex'));
    }

    public function headersAllow()
    {
        $headers = explode(PHP_EOL, $this->harvest->getResponse()->getHeaders(false));

        return RobotsHeaders::create($headers)->mayIndex($this->isIndexableFor);
    }

    public static function indexable(Harvest $harvest, string $isIndexableFor = 'googlebot'): int
    {
        $self = new self($harvest, $isIndexableFor);

        // robots
        if (! $self->robotsTxtAllows()) {
            return self::NOT_INDEXABLE_ROBOTS;
        }

        if (! $self->headersAllow()) {
            return self::NOT_INDEXABLE_HEADER;
        }

        if (! $self->metaAllows()) {
            return self::NOT_INDEXABLE_META;
        }

        // canonical
        if (! $harvest->isCanonicalCorrect()) {
            return self::NOT_INDEXABLE_CANONICAL;
        }

        $statusCode = $harvest->getResponse()->getStatusCode();

        // status 4XX
        if ($statusCode < 500 && $statusCode > 399) {
            return self::NOT_INDEXABLE_4XX;
        }

        // status 5XX
        if ($statusCode < 600 && $statusCode > 499) {
            return self::NOT_INDEXABLE_5XX;
        }

        // status 3XX
        if ($statusCode < 400 && $statusCode > 299) {
            return self::NOT_INDEXABLE_3XX;
        }

        return self::INDEXABLE;
    }
}
