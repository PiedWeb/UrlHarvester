<?php

namespace PiedWeb\UrlHarvester;

use phpuri;
use PiedWeb\Curl\Request as CurlRequest;
use PiedWeb\Curl\Response;
use PiedWeb\TextAnalyzer\Analyzer as TextAnalyzer;
use Spatie\Robots\RobotsHeaders;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Harvest
{
    use HarvestLinksTrait;
    use RobotsTxtTrait;

    const LINK_SELF = 1;
    const LINK_INTERNAL = 2;
    const LINK_SUB = 3;
    const LINK_EXTERNAL = 4;

    const DEFAULT_USER_AGENT = 'SeoPocketCrawler - Open Source Bot for SEO Metrics';

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $dom;

    /** @var string */
    protected $baseUrl;

    /** @var string */
    protected $domain;

    /** @var bool */
    protected $follow;

    /** @var string */
    private $domainWithScheme;

    /** @var \PiedWeb\TextAnalyzer\Analysis */
    private $textAnalysis;

    /**
     * @return self|int
     */
    public static function fromUrl(
        string $url,
        string $userAgent = self::DEFAULT_USER_AGENT,
        string $language = 'en,en-US;q=0.5',
        ?CurlRequest $previousRequest = null
    ) {
        $response = Request::makeFromRequest($previousRequest, $url, $userAgent, $language);

        if ($response instanceof Response) {
            return new self($response);
        }

        return $response;
    }

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
            $this->dom = new DomCrawler($this->response->getContent());
        }

        return $this->dom;
    }

    /**
     * @return DomCrawler
     */
    private function find($selector, $number = null)
    {
        if (null !== $number) {
            return $this->getDom()->filter($selector)->eq($number);
        }

        return $this->getDom()->filter($selector);
    }

    /**
     * Alias for find($selector, 0).
     *
     * @return DomCrawler
     */
    private function findOne($selector)
    {
        return $this->find($selector, 0);
    }

    /**
     * Return content inside a selector.
     * Eg.: getTag('title').
     *
     * @return string
     */
    public function getTag($selector)
    {
        $found = $this->findOne($selector);

        return null !== $found ? Helper::clean($found->text()) : null;
    }

    public function getUniqueTag($selector = 'title')
    {
        $found = $this->find($selector);

        if ($found->count() === 0) {
            return null;
        }

        if ($found->count() > 1) {
            return $found->count().' `'.$selector.'` /!\ ';
        }

        return Helper::clean($found->eq(0)->text());
    }

    /**
     * Return content inside a meta.
     *
     * @return string|null from content attribute
     */
    public function getMeta(string $name)
    {
        $meta = $this->findOne('meta[name='.$name.']');

        return $meta->count() > 0 ? (null !== $meta->attr('content') ? Helper::clean($meta->attr('content')) : '') : null;
    }

    /**
     * Renvoie le contenu de l'attribut href de la balise link rel=canonical.
     *
     * @return string le contenu de l'attribute href sinon NULL si la balise n'existe pas
     */
    public function getCanonical()
    {
        $canonical = $this->findOne('link[rel=canonical]');

        return $canonical->count() > 0 ? (null !== $canonical->attr('href') ? $canonical->attr('href') : '') : null;
    }

    /*
     * @return bool
     */
    public function isCanonicalCorrect()
    {
        $canonical = $this->getCanonical();

        return $canonical ? $this->response->getEffectiveUrl() == $canonical : true;
    }

    public function getTextAnalysis()
    {
        if (null === $this->textAnalysis) {
            $this->textAnalysis = TextAnalyzer::get(
                $this->getDom()->text(),
                true,   // only sentences
                1,      // no expression, just words
                0      // keep trail
            );
        }

        return $this->textAnalysis;
    }

    public function getKws()
    {
        return $this->getTextAnalysis()->getExpressions(10);
    }

    public function getRatioTxtCode(): int
    {
        $textLenght = strlen($this->getDom()->text());
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

    protected function metaAuthorizeToFollow()
    {
        return !(strpos($this->getMeta('googlebot'), 'nofollow') || strpos($this->getMeta('robots'), 'nofollow'));
    }

    public function mayFollow()
    {
        if (null === $this->follow) {
            $robotsHeaders = new RobotsHeaders($this->response->getHeaders());
            $this->follow = $robotsHeaders->mayFollow() && $this->metaAuthorizeToFollow() ? true : false;
        }

        return $this->follow;
    }
}
