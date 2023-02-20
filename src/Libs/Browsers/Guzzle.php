<?php

namespace Vannghia\GuzzlePromise\Libs\Browsers;

use GuzzleHttp\Client;

class Guzzle implements BrowserInterface {

    protected Client $client;

    /**
     * Guzzle constructor.
     * @todo: use proxy?
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->client = new Client($options);
    }


    public function getHtml($url)
    {
        $response = $this->client->get($url, ['proxy' => '118.70.13.36:6056']);
        $html = $response->getBody()->getContents();
        if (mb_stripos($html, "</a>") === false && mb_stripos($html, "<body") === false) {
            $html = mb_convert_encoding($html, "UTF-8", "UTF-16LE");
        } elseif (mb_stripos($html, "charset=Shift_JIS")) {
            $html = mb_convert_encoding($html, "UTF-8", "SJIS");
            $html = str_replace("charset=Shift_JIS", "charset=UTF-8", $html);
        }
        return $html;
    }

}
