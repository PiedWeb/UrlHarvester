<?php

namespace PiedWeb\UrlHarvester;

use PiedWeb\Curl\Request as CurlRequest;
use PiedWeb\Curl\Response;

/**
 * Request a page and get it only if it's an html page.
 */
class Request
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $proxy;

    /**
     * @var bool
     */
    private $tryHttps;

    /**
     * @var string
     */
    private $donwloadOnly;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param string $url
     * @param string $userAgent
     * @param string $language
     * @param bool   $tryHttps
     *
     * @return self
     */
    public static function make(
        string  $url,
        string  $userAgent,
        string  $donwloadOnly = 'text/html',
        string  $language = 'en,en-US;q=0.5',
        bool    $tryHttps = false,
        ?string $proxy = null
    ) {
        $request = new Request($url);

        $request->tryHttps     = $tryHttps;
        $request->userAgent    = $userAgent;
        $request->donwloadOnly = $donwloadOnly;
        $request->language     = $language;
        $request->proxy        = $proxy;

        $request->request();

        return $request;
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
     *
     * @return array
     */
    private function prepareHeadersForRequest()
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        $headers = [];
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'Accept-Language: '.$this->language;
        $headers[] = 'Connection: keep-alive';

        if ($host) {
            //$headers[] =  'Host: '.$host;
        }
        // Referer

        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: '.$this->userAgent;

        return $headers;
    }

    /**
     * @return self
     */
    private function request()
    {
        $this->request = new CurlRequest($this->url);
        $this->request
            ->setReturnHeader()
            ->setEncodingGzip()
            ->setDefaultSpeedOptions()
            ->setOpt(CURLOPT_SSL_VERIFYHOST, 0)
            ->setOpt(CURLOPT_SSL_VERIFYPEER, 0)
            ->setOpt(CURLOPT_MAXREDIRS, 0)
            ->setOpt(CURLOPT_COOKIE, false)
            ->setOpt(CURLOPT_CONNECTTIMEOUT, 20)
            ->setOpt(CURLOPT_TIMEOUT, 80);
        if ($this->donwloadOnly) {
            $this->request->setDownloadOnlyIf($this->donwloadOnly);
        }
        if ($this->proxy) {
            $this->request->setProxy($this->proxy);
        }
        $this->request->setOpt(CURLOPT_HTTPHEADER, $this->prepareHeadersForRequest());

        $this->response = $this->request->exec();

        // Recrawl https version if it's asked
        if (true === $this->tryHttps) {
            $httpsUrl = $this->amIRedirectToHttps();
            $requestForHttps = self::make($httpsUrl, $this->userAgent, $this->donwloadOnly, $this->language);
            if (!$requestForHttps->get()->hasError()) { // if no error, $this becode https request
                return $requestForHttps;
            }
        }

        return $this;
    }

    /**
     * @return CurlRequest
     */
    public function get()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string|false
     */
    private function amIRedirectToHttps()
    {
        $headers = array_change_key_case($this->response->getHeaders());
        $redirUrl = isset($headers['location']) ? $this->headers['location'] : null;
        if (null !== $redirUrl && ($httpsUrl = preg_replace('#^http://#', 'https://', $this->url, 1)) == $redirUrl) {
            return $httpsUrl;
        }

        return false;
    }
}