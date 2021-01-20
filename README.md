<p align="center"><a href="https://dev.piedweb.com">
<img src="https://raw.githubusercontent.com/PiedWeb/piedweb-devoluix-theme/master/src/img/logo_title.png" width="200" height="200" alt="Open Source Package" />
</a></p>

# Url Meta Data Harvester

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/UrlHarvester.svg?style=flat&label=release)](https://github.com/PiedWeb/UrlHarvester/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/PiedWeb/UrlHarvester/Tests?label=tests)](https://github.com/PiedWeb/UrlHarvester/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/UrlHarvester.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/UrlHarvester)
[![Code Coverage](https://codecov.io/gh/PiedWeb/UrlHarvester/branch/main/graph/badge.svg)](https://codecov.io/gh/PiedWeb/UrlHarvester/branch/main)
[![Type Coverage](https://shepherd.dev/github/PiedWeb/UrlHarvester/coverage.svg)](https://shepherd.dev/github/PiedWeb/UrlHarvester)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/url-harvester.svg?style=flat)](https://packagist.org/packages/piedweb/url-harvester)

Harvest statistics and meta data from an URL or his source code (seo oriented).

Implemented in [Seo Pocket Crawler](https://piedweb.com/seo/crawler) ([source on github](https://github.com/PiedWeb/SeoPocketCrawler)).

## Install

Via [Packagist](https://img.shields.io/packagist/dt/piedweb/url-harvester.svg?style=flat)

```bash
$ composer require piedweb/url-harvester
```

## Usage

Harvest Methods :

```php
use \PiedWeb\UrlHarvester\Harvest;
use \PiedWeb\UrlHarvester\Link;

$url = 'https://piedweb.com';

Harvest::fromUrl($url)
    ->getResponse()->getInfo('total_time') // load time
    ->getResponse()->getInfo('size_download')
    ->getResponse()->getStatusCode()
    ->getResponse()->getContentType()
    ->getRes...

    ->getTag('h1') // @return first tag content (could be html)
    ->getUniqueTag('h1') // @return first tag content in utf8 (could contain html)
    ->getMeta('description') // @return string from content attribute or NULL
    ->getCanonical() // @return string|NULL
    ->isCanonicalCorrect() // @return bool
    ->getRatioTxtCode() // @return int
    ->getTextAnalysis() // @return \PiedWeb\TextAnalyzer\Analysis
    ->getKws() // @return 10 more used words
    ->getBreadCrumb()
    ->indexable($userAgent = 'googlebot') // @return int corresponding to a const from Indexable

    ->getLinks()
    ->getLinks(Link::LINK_SELF)
    ->getLinks(Link::LINK_INTERNAL)
    ->getLinks(Link::LINK_SUB)
    ->getLinks(Link::LINK_EXTERNAL)
    ->getLinkedRessources() // Return an array with all attributes containing a href or a src property
    ->mayFollow() // check headers and meta and return bool

    ->getDomain()
    ->getBaseUrl()

    ->getRobotsTxt() // @return \Spatie\Robots\RobotsTxt or empty string
    ->setRobotsTxt($content) // @param string or RobotsTxt
```

## Testing

```bash
$ composer test
```

## Contributing

Please see [contributing](https://dev.piedweb.com/contributing)

## Credits

- [Pied Web](https://piedweb.com)
- [All Contributors](https://github.com/PiedWeb/:package_skake/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
