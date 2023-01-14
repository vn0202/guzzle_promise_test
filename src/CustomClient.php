<?php

namespace Vannghia\GuzzlePromise;

use GuzzleHttp\Client;

class CustomClient extends Client
{

    public function __construct(private  ?Client $client = null )
    {
        parent::__construct();
     if($this->client == null){
         $this->client = new Client();
     }
     else{
         $this->client = $this->client;
     }
    }

}