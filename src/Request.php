<?php

namespace PiedWeb\UrlHarvester;

use PiedWeb\Curl\Request as CurlRequest;
use PiedWeb\Curl\Response;

/**
 * Request a page and get it only if it's an html page.
 */
class Request
{
    private string $url;

    private string $userAgent;

    private string $language;

    private string $proxy;

    public int $maxSize = 1000000;

    /**
     * @param bool $tryHttps
     *
     * @return Response|int corresponding to the curl error
     */
    public static function make(
        string $url,
        string $userAgent,
        string $language = 'en,en-US;q=0.5',
        ?string $proxy = null,
        int $documentMaxSize = 1000000
    ) {
        return self::makeFromRequest(null, $url, $userAgent, $language, $proxy, $documentMaxSize);
    }

    public static function makeFromRequest(
        ?CurlRequest $curlRequest = null,
        string $url,
        string $userAgent,
        string $language = 'en,en-US;q=0.5',
        ?string $proxy = null,
        int $documentMaxSize = 1000000
    ) {
        $request = new Request($url);

        $request->userAgent = $userAgent;
        $request->language = $language;
        $request->proxy = $proxy;
        $request->maxSize = $documentMaxSize;

        return $request->request($curlRequest);
    }

    private function __construct($url)
    {
        /*
        if (!filter_var($string, FILTER_VALIDATE_URL)) {
            throw new \Exception('URL invalid: '.$string);
        }**/
        $this->url = $url;
    }

    /**
     * Prepare headers as a normal browser (same order, same content).
     */
    private function prepareHeadersForRequest(): array
    {
        //$host = parse_url($this->url, PHP_URL_HOST);

        $headers = [];
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'Accept-Language: '.$this->language;
        $headers[] = 'Connection: keep-alive';

        //if ($host) {
        //$headers[] =  'Host: '.$host;
        //}
        // Referer

        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: '.$this->userAgent;

        return $headers;
    }

    /**
     * @return Response|int corresponding to the curl error
     */
    private function request(?CurlRequest $request = null)
    {
        $request = null !== $request ? $request : new CurlRequest();
        $request
            ->setUrl($this->url)
            ->setReturnHeader()
            ->setEncodingGzip()
            ->setUserAgent($this->userAgent)
            ->setDefaultSpeedOptions()
            ->setOpt(CURLOPT_SSL_VERIFYHOST, 0)
            ->setOpt(CURLOPT_SSL_VERIFYPEER, 0)
            ->setOpt(CURLOPT_MAXREDIRS, 0)
            ->setOpt(CURLOPT_FOLLOWLOCATION, false)
            ->setOpt(CURLOPT_COOKIE, false)
            ->setOpt(CURLOPT_CONNECTTIMEOUT, 20)
            ->setOpt(CURLOPT_TIMEOUT, 80)
            ->setAbortIfTooBig($this->maxSize); // 2Mo

        if ($this->proxy) {
            $request->setProxy($this->proxy);
        }

        $request->setOpt(CURLOPT_HTTPHEADER, $this->prepareHeadersForRequest());

        $response = $request->exec();
        //dd($this->request->exec());

        return $response;
    }
}
