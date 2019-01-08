<?php

namespace PiedWeb\UrlHarvester;

use PiedWeb\Curl\Response;
use PiedWeb\TextAnalyzer\Analyzer as TextAnalyzer;
use phpuri;
use simple_html_dom;

class Harvest
{
    use HarvestLinksTrait;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var simple_html_dom
     */
    protected $dom;

    public static function fromUrl(
        string $url,
        string $userAgent = 'Bot: Url Harvester',
        string $language = 'en,en-US;q=0.5',
        bool   $tryHttps = false
    ) {
        $request = Request::make($url, $userAgent, 'text/html', $language, $tryHttps);
        $response = $request->getResponse();

        if ($response instanceof Response) {
            return new self($response);
        }

        return $request->getError();
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
        $headers = array_change_key_case($this->response->getHeaders());
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

        return null !== $meta ? (isset($meta->content) ? Helper::clean($meta->content) : '') : null;
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
        return $this->response->getEffectiveUrl() == $this->getCanonical();
    }

    public function getKws()
    {
        $kws = TextAnalyzer::get(
            $this->response->getContent(),
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
     * @return array or NULL if we didn't found breadcrumb
     */
    public function getBreadCrumb()
    {
        return ExtractBreadcrumb::get(
            $this->response->getContent(),
            $this->getBaseUrl(),
            $this->response->getEffectiveUrl()
        );
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
}
