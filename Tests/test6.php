<?php

require "../vendor/autoload.php";

use Serps\SearchEngine\Google\Exception\InvalidDOMException;
use Serps\SearchEngine\Google\GoogleClient;
use Serps\HttpClient\CurlClient;
use Serps\SearchEngine\Google\GoogleUrl;
use Serps\Core\Browser\Browser;




//$client = new GoogleSearchResults('f988d9b1e52e5ed5118a544e2fa44fd5b75716980346ff4301f59924205df3b9');
//$query = ["q" => "site:google.com"];
//$response = $client->get_json($query,['proxy'=>"196.51.27.240:8800"]);
//print_r($response->search_information->total_results);

$browser  = \Vannghia\GuzzlePromise\Libs\Browsers\BrowserManager::get('browserless',['proxy'=>['196.51.27.209:8800']]);

  $url = "https://google.com/search?q="."site:webcoban.com";
$html = $browser->getHtml($url);
$crawler = new \Symfony\Component\DomCrawler\Crawler();
$crawler->addHtmlContent($html);

dd($crawler->filter('#result-stats')->innerText());


