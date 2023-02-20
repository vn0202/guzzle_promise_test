<?php

namespace Vannghia\GuzzlePromise\Libs\Browsers;

use DokLibs\Browserless\Client;

class Browserless implements BrowserInterface
{

    protected Client $client;

    /**
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        if(!isset($options['servers'])){
            throw new \Exception("Servers config required");
        }

        $this->client = new Client($options['servers']);

    }


    public function getHtml($url)
    {
        $response = $this->client->content($url,
            (new \DokLibs\Browserless\Options\CommonOptions())
                ->setRejectResourceTypes('stylesheet', 'image', 'media', 'font', 'xhr', 'script')
        );

        return $response->getBody()->getContents();
    }


}
