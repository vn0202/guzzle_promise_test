<?php

namespace Vannghia\GuzzlePromise\Libs;

use League\Uri\Uri;
use League\Uri\UriResolver;

class CrawlerHelper
{
    public static function makeFullUrl($referer, $href)
    {
        $href = trim($href);
        $href = rtrim($href, "/");

        if (str_contains($href, "//")) {
            return $href;
        }

        $href = UriResolver::resolve(
            Uri::createFromString($href),
            Uri::createFromString($referer),
        )->__toString();

        return rtrim($href, "/");
    }


}