<?php

namespace Vannghia\GuzzlePromise\Libs\Browsers;

class BrowserManager
{
    protected static $config= [
        'guzzle' => [
            'verify' => false,
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            ],
            'http_errors' => false,
        ],
        'browserless' => [
            "servers" => [
                ["118.70.13.36:6030",""],
            ]
        ],
    ];
    protected static $drivers = [];

    /**
     * @param $driver
     *
     * @return BrowserInterface
     * @throws \Exception
     */
    public static function get($driver,array $options=[])
    {
        if (!isset(self::$drivers[$driver])) {
            self::$drivers[$driver] = self::makeBrowser($driver,$options);
        }
        return self::$drivers[$driver];
    }

    /**
     * @param $driver
     *
     * @return BrowserInterface
     * @throws \Exception
     */
    protected static function makeBrowser($driver,array $options= [])
    {
        switch ($driver) {
            case "guzzle":
                $options = array_merge($options,self::$config['guzzle']);
                return new Guzzle($options);
            case "browserless":
                $options = array_merge($options,self::$config['browserless']);
                return new Browserless($options);
            default:
                throw new \Exception("No browser match with driver " . $driver);
        }
    }

}