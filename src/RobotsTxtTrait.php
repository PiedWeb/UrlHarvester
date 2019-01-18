<?php

namespace PiedWeb\UrlHarvester;

use Spatie\Robots\RobotsTxt;
use PiedWeb\Curl\Request as CurlRequest;

trait RobotsTxtTrait
{
    /** @var RobotsTxt|string (empty string) */
    protected $robotsTxt;

    abstract public function getDomainAndScheme();

    abstract public function getResponse();

    /**
     * @return RobotsTxt|string containing the current Robots.txt or NULL if an error occured
     *                          or empty string if robots is empty file
     */
    public function getRobotsTxt()
    {
        if (null === $this->robotsTxt) {
            $url = $this->getDomainAndScheme().'/robots.txt';

            $request = $this->getResponse()->getRequest();
            $userAgent = $request ? $request->getUserAgent() : self::DEFAULT_USER_AGENT;

            $request = new CurlRequest($url);
            $request
                ->setDefaultSpeedOptions()
                ->setDownloadOnly('0-500000')
                ->setUserAgent($userAgent)
            ;
            $result = $request->exec();

            if (!$result instanceof \PiedWeb\Curl\Response
                || false === stripos($result->getContentType(), 'text/plain')
                || empty(trim($result->getContent()))
            ) {
                $this->robotsTxt = '';
            } else {
                $this->robotsTxt = new RobotsTxt($result->getContent());
            }
        }

        return $this->robotsTxt;
    }

    /**
     * @param RobotsTxt|string $robotsTxt
     *
     * @return self
     */
    public function setRobotsTxt($robotsTxt)
    {
        $this->robotsTxt = is_string($robotsTxt) ? (empty($robotsTxt) ? '' : new RobotsTxt($robotsTxt)) : $robotsTxt;

        return $this;
    }
}
