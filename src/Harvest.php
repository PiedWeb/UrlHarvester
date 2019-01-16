<?php

namespace PiedWeb\UrlHarvester;

use PiedWeb\Curl\Response;
use PiedWeb\TextAnalyzer\Analyzer as TextAnalyzer;
use phpuri;
use simple_html_dom;
use Spatie\Robots\RobotsTxt;
use PiedWeb\Curl\Request as CurlRequest;

class Harvest
{
    use HarvestLinksTrait;

    const LINK_SELF = 1;
    const LINK_INTERNAL = 2;
    const LINK_SUB = 3;
    const LINK_EXTERNAL = 4;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var simple_html_dom
     */
    protected $dom;

    /** @var string */
    protected $baseUrl;

    /** @var string */
    protected $domain;

    /** @var RobotsTxt|string (empty string) */
    protected $robotsTxt;

    /** @var string */
    private $domainWithScheme;

    /**
     * @return self|int
     */
    public static function fromUrl(
        string $url,
        string $userAgent = 'SeoPocketCrawler - Open Source Bot for SEO Metrics',
        string $language = 'en,en-US;q=0.5',
        ?CurlRequest $previousRequest = null
    ) {
        $response = Request::makeFromRequest($previousRequest, $url, $userAgent, $language);

        if ($response instanceof Response) {
            return new self($response);
        }

        return $response;
    }

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRedirection()
    {
        $headers = $this->response->getHeaders();
        $headers = array_change_key_case($headers ? $headers : []);
        if (isset($headers['location'])) {
            return phpUri::parse($this->response->getEffectiveUrl())->join($headers['location']);
        }

        return false;
    }

    public function getDom()
    {
        if (null === $this->dom) {
            $this->dom = new simple_html_dom();
            $this->dom->load($this->response->getContent());
        }

        return $this->dom;
    }

    private function find($selector, $number = null)
    {
        return $this->getDom()->find($selector, $number);
    }

    private function findOne($selector)
    {
        return $this->find($selector, 0);
    }

    /**
     * Return content inside a selector.
     *
     * @return string
     */
    public function getTag($selector)
    {
        $found = $this->findOne($selector);

        return null !== $found ? Helper::clean($found->innertext) : null;
    }

    public function getUniqueTag($selector = 'title')
    {
        $found = $this->find($selector);
        if ($found) {
            if (count($found) > 1) {
                return count($found).' `'.$selector.'` !!';
            } else {
                return Helper::clean($found[0]->innertext);
            }
        }
    }

    /**
     * Return content inside a meta.
     *
     * @return string from content attribute
     */
    public function getMeta(string $name)
    {
        $meta = $this->findOne('meta[name='.$name.']');

        return null !== $meta ? (isset($meta->content) ? Helper::clean($meta->content) : '') : '';
    }

    /**
     * Renvoie le contenu de l'attribut href de la balise link rel=canonical.
     *
     * @return string le contenu de l'attribute href sinon NULL si la balise n'existe pas
     */
    public function getCanonical()
    {
        $canonical = $this->findOne('link[rel=canonical]');

        return null !== $canonical ? (isset($canonical->href) ? $canonical->href : '') : null;
    }

    /*
     * @return bool
     */
    public function isCanonicalCorrect()
    {
        $canonical = $this->getCanonical();

        return $canonical ? $this->response->getEffectiveUrl() == $canonical : true;
    }

    public function getKws()
    {
        $kws = TextAnalyzer::get(
            $this->getDom(),
            true,   // only sentences
            1,      // no expression, just words
            0      // keep trail
        );

        return $kws->getExpressions(10);
    }

    /**
     * @return int
     */
    public function getRatioTxtCode(): int
    {
        $textLenght = strlen($this->getDom()->plaintext);
        $htmlLenght = strlen(Helper::clean($this->response->getContent()));

        return (int) ($htmlLenght > 0 ? round($textLenght / $htmlLenght * 100) : 0);
    }

    /**
     * Return an array of object with two elements Link and anchor.
     *
     * @return array|null if we didn't found breadcrumb
     */
    public function getBreadCrumb(?string $separator = null)
    {
        $breadcrumb = ExtractBreadcrumb::get(
            $this->response->getContent(),
            $this->getBaseUrl(),
            $this->response->getEffectiveUrl()
        );

        if (null !== $separator && is_array($breadcrumb)) {
            $breadcrumb = array_map(function ($item) {
                return $item->getCleanName();
            }, $breadcrumb);
            $breadcrumb = implode($separator, $breadcrumb);
        }

        return $breadcrumb;
    }

    /**
     * @return string|false
     */
    public function amIRedirectToHttps()
    {
        $headers = $this->response->getHeaders();
        $headers = array_change_key_case(null !== $headers ? $headers : []);
        $redirUrl = isset($headers['location']) ? $headers['location'] : null;
        $url = $this->response->getUrl();
        if (null !== $redirUrl && ($httpsUrl = preg_replace('#^http:#', 'https:', $url, 1)) == $redirUrl) {
            return $httpsUrl;
        }

        return false;
    }

    public function getBaseUrl()
    {
        if (!isset($this->baseUrl)) {
            $base = $this->findOne('base');
            if (null !== $base && isset($base->href) && filter_var($base->href, FILTER_VALIDATE_URL)) {
                $this->baseUrl = $base->href;
            } else {
                $this->baseUrl = $this->response->getEffectiveUrl();
            }
        }

        return $this->baseUrl;
    }

    public function getDomain()
    {
        if (!isset($this->domain)) {
            $urlParsed = parse_url($this->response->getEffectiveUrl());
            preg_match("/[^\.\/]+(\.com?)?\.[^\.\/]+$/", $urlParsed['host'], $match);
            $this->domain = $match[0];
        }

        return $this->domain;
    }

    /**
     * @return int correspond to a const from Indexable
     */
    public function isIndexable(string $userAgent = 'googlebot')
    {
        return Indexable::isIndexable($this, $userAgent);
    }

    /**
     * @return RobotsTxt|string containing the current Robots.txt or NULL if an error occured
     *                          or empty string if robots is empty file
     */
    public function getRobotsTxt()
    {
        if (null === $this->robotsTxt) {
            $url = $this->getDomainAndScheme().'/robots.txt';

            $request = new CurlRequest($url);
            $request
                ->setDefaultSpeedOptions()
                ->setDownloadOnly('0-500000')
                ->setUserAgent($this->getResponse()->getRequest()->getUserAgent())
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

    public function getDomainAndScheme()
    {
        if (null === $this->domainWithScheme) {
            $this->domainWithScheme = self::getDomainAndSchemeFrom($this->response->getEffectiveUrl());
        }

        return $this->domainWithScheme;
    }

    public static function getDomainAndSchemeFrom(string $url)
    {
        $url = parse_url($url);

        return $url['scheme'].'://'.$url['host'];
    }
}
