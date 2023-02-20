<?php
require "../vendor/autoload.php";

use Vannghia\GuzzlePromise\CustomClient;
dd("..".__DIR__. "/vendor/autoload.php");
$proxy = [
    '23.254.100.211:8800',
'104.227.229.216:8800',
'23.254.100.184:8800',
'192.126.248.170:8800',
'196.51.27.220:8800',
'192.126.198.83:8800',
'45.57.187.248:8800',
'45.57.187.22:8800',
'192.126.198.163:8800',
'196.51.27.209:8800',
'192.126.198.228:8800',
'192.126.198.159:8800',
'196.51.27.240:8800',
'104.227.229.238:8800',
'192.126.248.100:8800',
'104.227.229.211:8800',
'192.126.248.94:8800',
'23.254.100.132:8800',
'45.57.187.1:8800',
'23.254.100.155:8800',
'196.51.27.196:8800',
'196.51.27.189:8800',
'45.57.187.189:8800',
'104.227.229.170:8800',
'192.126.248.238:8800',
];
function accessByManyProxy( string $uri, array $proxy)
{

    $total = count($proxy);
    $client = new CustomClient();
    $requests = function () use ($uri,$total,$proxy) {
        for ($i = 0; $i < $total; $i++) {

                yield new \GuzzleHttp\Psr7\Request('GET', $uri);
        }
    };
    $pool = new \GuzzleHttp\Pool($client, $requests(), [
        'concurrency' => 5,
        'fulfilled' => function (\GuzzleHttp\Psr7\Response $response, $index) {
            // this is delivered each successful response
            echo 'fullfiled'. $index;
        },
        'rejected' => function (\GuzzleHttp\Exception\RequestException $reason, $index) {
            // this is delivered each failed request
            echo 'error'. $index;
        },

    ]);

// Initiate the transfers and create a promise
    $promise = $pool->promise();

// Force the pool of requests to complete.
    $promise->wait();

}

$client = new \GuzzleHttp\Client();


function getAllLinkPerUrl(string $url,$client)
{
    $response = $client->get($url);
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);

        $link = $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $dom){
            return ['href'=>$dom->attr('href')];
        });
        foreach ( $link as $item)
        {
            yield $item;
        }
}


function getInforUrl(string $url,$client)
{
    $result = [];
    $response = $client->get('https://dantri.com.vn');
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);
    $result['url']= $url;
    $result['title']= $crawler->filter('title')->text();
    return $result;

}

function getPromise(string $url , $client)
{
  foreach (getAllLinkPerUrl($url, $client) as $link )
  {
      $promise = $client->getAsync($url);
      yield $promise;
  }

}

//function getPromise($uri, array $proxy)
//{
//    $client = new \GuzzleHttp\Client(['timeout'=>2.0]);
//    foreach ($proxy  as $item) {
//        $promises = $client->getAsync($uri, ['proxy'=>$item]);
//        yield $promises;
//    }
//}

function accessByManyProxy2(string $uri, array $proxy)
{

    echo  "total proxy: ".count($proxy);
    (new \GuzzleHttp\Promise\EachPromise(getPromise($uri, $proxy),
   [
       'concurrency'=>5,
       'fulfilled' => function (\Psr\Http\Message\ResponseInterface $response, $index) {
            dump([
                'state'=>$response->getStatusCode(),
                'Ip'=>json_decode($response->getBody()->getContents())->ip,
                'index'=>$index,
            ]);
       },
       'rejected' => function (\GuzzleHttp\Exception\TransferException $exception, $index) {
          dump( $index. " has rejected with ". $exception->getMessage());
       },
   ]))->promise()->wait();


}


