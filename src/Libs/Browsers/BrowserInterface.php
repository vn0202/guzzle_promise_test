<?php

namespace Vannghia\GuzzlePromise\Libs\Browsers;


interface BrowserInterface {

    public function __construct($options = []);

    public function getHtml($url);
    
}