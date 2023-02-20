<?php
require "../vendor/autoload.php";




use Illuminate\Database\Capsule\Manager as Capsule;


$client = \Vannghia\GuzzlePromise\Libs\Browsers\BrowserManager::get('browserless');
$crawler = new \Symfony\Component\DomCrawler\Crawler();
$response = $client->getHtml('https://www.google.com/search?q=excel+site%3Ahttps%3A%2F%2Fwww.facebook.com%2Fgroups%2F&sxsrf=AJOqlzX5xGkFH88YoOMjpyQ9k4xY2EU4uw%3A1676874585863&ei=WRPzY7isNIH4hwPjqJSYBQ&oq=ex&gs_lcp=Cgxnd3Mtd2l6LXNlcnAQAxgAMgQIIxAnMgQIIxAnMgQIIxAnMgQIABBDMgQIABBDMgUIABCABDIRCC4QgAQQxwEQ0QMQ0gMQqAMyBQgAEIAEMgUIABCABDIFCAAQgAQ6BggjECcQEzoaCC4QgAQQxwEQ0QMQqAMQ0gMQiwMQqAMQ0gM6EQguEIAEEMcBENEDEKgDENIDOgsILhCABBCdAxCoAzoHCAAQgAQQCjoQCC4QxwEQ0QMQqAMQ0gMQQzoaCC4QgAQQxwEQ0QMQqAMQ0gMQiwMQ0gMQqAM6FAguEIAEEMcBENEDENQCEKgDENIDOgsILhCABBCZAxCoA0oECEEYAVD7BliWHWDyI2gBcAB4AIAB6AKIAZ0QkgEDMy02mAEAoAEBuAECwAEB&sclient=gws-wiz-serp');

$crawler = new \Symfony\Component\DomCrawler\Crawler();
$crawler->addHtmlContent($response);
$crawler->filterXPath('//body//a')->each(function( $dom_crawler){
    dump($dom_crawler->attr('href'));
});


