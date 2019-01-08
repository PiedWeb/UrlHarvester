<p align="center"><a href="https://dev.piedweb.com">
<img src="https://raw.githubusercontent.com/PiedWeb/piedweb-devoluix-theme/master/src/img/logo_title.png" width="200" height="200" alt="Open Source Package" />
</a></p>

# Url Meta Data Harvester

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/UrlHarvester.svg?style=flat&label=release)](https://github.com/PiedWeb/UrlHarvester/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](https://github.com/PiedWeb/UrlHarvester/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/UrlHarvester/master.svg?style=flat)](https://travis-ci.org/PiedWeb/UrlHarvester)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/UrlHarvester.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/UrlHarvester)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/UrlHarvester.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/UrlHarvester/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/url-harvester.svg?style=flat)](https://packagist.org/packages/piedweb/url-harvester)

Harvest statistics and meta data from an URL or his source code (seo oriented).

## Install

Via [Packagist](https://img.shields.io/packagist/dt/piedweb/url-harvester.svg?style=flat)

``` bash
$ composer require piedweb/url-harvester
```

## Usage

Harvest Methods :

```php
use \PiedWeb\UrlHarvester\Harvest;

$url = 'https://piedweb.com';

Harvest::fromUrl($url)
    ->getResponse()->getInfo('total_time') // load time
    ->getResponse()->getInfo('size_download')
    ->getResponse()->getStatusCode()
    ->getResponse()->getContentType()
    ->getRes...

    ->getTag('h1') // @return first tag content (could be html)
    ->getMeta('description') // @return string from content attribute or NULL
    ->getCanonical() // @return string|NULL
    ->isCanonicalCorrect() // @return bool
    ->getRatioTxtCode() // @return int
    ->getKws() // @return 10 more used words
    ->getBreadCrumb()

    ->getLinks()
    ->getLinks('internal')
    ->getLinks('self')
    ->getLinks('sub')
    ->getLinks('external')

    ->getDomain()
    ->getBaseUrl()
```

All others methods:
```php
use \PiedWeb\UrlHarvester\Request;
use \PiedWeb\UrlHarvester\ExtractLinks;
use \PiedWeb\UrlHarvester\ExtractBreadcrumb;

Request::make(string $url, string $userAgent, $donwloadOnly = 'text/html', string $language = 'en-US,en;q=0.5', bool $tryHttps = false, ?string $proxy = null)
  ->getResponse();      // @return \PiedWeb\Curl\Response;
  ->get();      // @return \PiedWeb\Curl\Request;
  ->get()->hasError(); // Useful @return int with curl error number


ExtractLinks::get(\simple_html_dom $dom, string $baseUrl, $selector = ExtractLinks::SELECT_A); // @return array

ExtractBreadcrumb::get($source, $baseUrl, $current = null); // @return array

```

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing](https://dev.piedweb.com/contributing)

## Credits

- [PiedWeb](https://piedweb.com)
- [All Contributors](https://github.com/PiedWeb/:package_skake/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/UrlHarvester.svg?style=flat&label=release)](https://github.com/PiedWeb/UrlHarvester/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](https://github.com/PiedWeb/UrlHarvester/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/UrlHarvester/master.svg?style=flat)](https://travis-ci.org/PiedWeb/UrlHarvester)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/UrlHarvester.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/UrlHarvester)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/UrlHarvester.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/UrlHarvester/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/url-harvester.svg?style=flat)](https://packagist.org/packages/piedweb/url-harvester)
