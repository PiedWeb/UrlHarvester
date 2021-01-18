<?php

namespace PiedWeb\UrlHarvester;

use PiedWeb\Curl\Request as CurlRequest;
use PiedWeb\Curl\Response;
use PiedWeb\TextAnalyzer\Analyzer as TextAnalyzer;
use Spatie\Robots\RobotsHeaders;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Harvest
{
    use HarvestLinksTrait;
    use RobotsTxtTrait;

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

    /** @var bool */
    protected $follow;

    /** @var \PiedWeb\TextAnalyzer\Analysis */
    private $textAnalysis;

    /** @var Url */
    protected $urlRequested;

    /** @var Url */
    protected $url;

    /**
     * @return self|int
     */
    public static function fromUrl(
        string $url,
        string $userAgent = self::DEFAULT_USER_AGENT,
        string $language = 'en,en-US;q=0.5',
        ?CurlRequest $previousRequest = null
    ) {
        $url = Link::normalizeUrl($url); // add trailing slash for domain

        $response = Request::makeFromRequest($previousRequest, $url, $userAgent, $language);

        if ($response instanceof Response) {
            return new self($response);
        }

        return $response;
    }

    public function __construct(Response $response)
    {
        $this->response = $response;

        $this->url = new Url($this->response->getEffectiveUrl());
        $this->urlRequested = new Url($this->response->getUrl());
    }

    public function urlRequested(): Url
    {
        return $this->urlRequested;
    }

    /**
     * Return url response (curl effective url)
     * // todo : check if urlRequested can be diffenrent than url (depends on curl wrench).
     */
    public function url(): Url
    {
        return $this->url;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getDom()
    {
        $this->dom = $this->dom ?? new DomCrawler($this->response->getContent());

        return $this->dom;
    }

    private function find($selector, $i = null): DomCrawler
    {
        return null !== $i ? $this->getDom()->filter($selector)->eq($i) : $this->getDom()->filter($selector);
    }

    /**
     * Alias for find($selector, 0).
     */
    private function findOne($selector): DomCrawler
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

        return $found->count() > 0 ? Helper::clean($found->text()) : null;
    }

    public function getUniqueTag($selector = 'title')
    {
        $found = $this->find($selector);

        if (0 === $found->count()) {
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
    public function getMeta(string $name): ?string
    {
        $meta = $this->findOne('meta[name='.$name.']');

        return $meta->count() > 0 ? (null !== $meta->attr('content') ? Helper::clean($meta->attr('content')) : '')
            : null;
    }

    /**
     * Renvoie le contenu de l'attribut href de la balise link rel=canonical.
     */
    public function getCanonical(): ?string
    {
        $canonical = $this->findOne('link[rel=canonical]');

        return $canonical->count() > 0 ? (null !== $canonical->attr('href') ? $canonical->attr('href') : '') : null;
    }

    /*
     * @return bool true si canonical = url requested or no canonical balise
     */
    public function isCanonicalCorrect(): bool
    {
        $canonical = $this->getCanonical();

        return null === $canonical ? true : $this->urlRequested()->get() == $canonical;
    }

    public function getTextAnalysis()
    {
        if (null !== $this->textAnalysis) {
            return $this->textAnalysis;
        }

        return $this->textAnalysis = $this->getDom()->count() > 0 ? TextAnalyzer::get(
            $this->getDom()->text(),
            true,   // only sentences
            1,      // no expression, just words
            0      // keep trail
        ) : null;
    }

    public function getWordCount(): int
    {
        return str_word_count($this->getDom()->text('') ?? '');
    }

    public function getKws()
    {
        return $this->getTextAnalysis()->getExpressions(10);
    }

    public function getRatioTxtCode(): int
    {
        $textLenght = strlen($this->getDom()->text(''));
        $htmlLenght = strlen(Helper::clean($this->response->getContent()));

        return (int) ($htmlLenght > 0 ? round($textLenght / $htmlLenght * 100) : 0);
    }

    /**
     * Return an array of object with two elements Link and anchor.
     */
    public function getBreadCrumb(?string $separator = null): ?array
    {
        $breadcrumb = ExtractBreadcrumb::get($this);

        if (null !== $separator && is_array($breadcrumb)) {
            $breadcrumb = array_map(function ($item) {
                return $item->getCleanName();
            }, $breadcrumb);
            $breadcrumb = implode($separator, $breadcrumb);
        }

        return $breadcrumb;
    }

    /**
     * @return ?string absolute url
     */
    public function getRedirection(): ?string
    {
        $headers = $this->response->getHeaders();
        $headers = array_change_key_case($headers ? $headers : []);
        if (isset($headers['location']) && ExtractLinks::isWebLink($headers['location'])) {
            return $this->url()->resolve($headers['location']);
        }

        return null;
    }

    public function getRedirectionLink(): ?Link
    {
        $redirection = $this->getRedirection();

        if (null !== $redirection) {
            return Link::createRedirection($redirection, $this);
        }

        return null;
    }

    public function isRedirectToHttps(): bool
    {
        $redirUrl = $this->getRedirection();

        return null !== $redirUrl && preg_replace('#^http:#', 'https:', $this->urlRequested()->get(), 1) == $redirUrl;
    }

    /**
     * Return the value in base tag if exist, else, current Url.
     */
    public function getBaseUrl(): string
    {
        if (! isset($this->baseUrl)) {
            $base = $this->findOne('base');
            if (null !== $base && isset($base->href) && filter_var($base->href, FILTER_VALIDATE_URL)) {
                $this->baseUrl = $base->href;
            } else {
                $this->baseUrl = $this->url()->get();
            }
        }

        return $this->baseUrl;
    }

    /**
     * @return int correspond to a const from Indexable
     */
    public function indexable(string $userAgent = 'googlebot'): int
    {
        return Indexable::indexable($this, $userAgent);
    }

    public function isIndexable(string $userAgent = 'googlebot'): bool
    {
        return Indexable::INDEXABLE === $this->indexable($userAgent);
    }

    protected function metaAuthorizeToFollow()
    {
        return ! (strpos($this->getMeta('googlebot'), 'nofollow') || strpos($this->getMeta('robots'), 'nofollow'));
    }

    public function mayFollow()
    {
        if (null === $this->follow) {
            $robotsHeaders = new RobotsHeaders((array) $this->response->getHeaders());
            $this->follow = $robotsHeaders->mayFollow() && $this->metaAuthorizeToFollow() ? true : false;
        }

        return $this->follow;
    }
}
