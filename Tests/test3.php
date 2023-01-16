<?php
require "../vendor/autoload.php";

use Vannghia\GuzzlePromise\CustomClient;
use Vannghia\SimpleQueryBuilder\Config\Connection;
use Vannghia\GuzzlePromise\Url;
use Medoo\Medoo;
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'guzzle_promise',
    'username' => 'root',
    'password' => 'root'
]);

$client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make();
$base_url = "https://dantri.com.vn";

//check whether a row  exits or not
function isExistRow($field, $value)
{
    global  $database;
    global $table;
    if ($database->get($table,'id',[$field=>$value])) {
        return true;
    }
    return false;

}

//get all link that have existed in a link  and save to db
function getAllLinkPerUrl($crawler)
{
    $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $dom){

        global $database;
        global $base_url;
        global $table;
        if ($dom->attr('href') !== '/' &&
            (str_starts_with($dom->attr('href'), '/') ||
                str_starts_with($dom->attr('href'), $base_url))) {
            //create a full url by base_url and href 
            $url = \Vannghia\GuzzlePromise\Libs\CrawlerHelper::makeFullUrl($base_url, $dom->attr('href'));
            $data['url'] = $url;
            $data['hash'] = md5($url);
            $data['is_go'] = 0;
            if(!isExistRow('hash', $data['hash']))
            {
                $database->insert($table, $data);
            }

        }


    });

}

//set additional infor url that going into to get all other links inside it
function getInforUrlGoingTo(\Psr\Http\Message\ResponseInterface $response, $url)
{
    global $database;
    global $table;
    dump("==================== go to ==================" . $url);
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);
    $title = $crawler->filter('title')->text();
    $hash = md5($url);


    getAllLinkPerUrl($crawler);

    dump($url);
    //addition infor title and set field is_go to 1 
    $database->update($table,['is_go'=>1, 'title'=>$title],['hash'=>$hash]);

}

function getPromise()
{
    global  $database;
    global $table;
    $client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);
    //get list links that have not gone into
 $list_link_un_go = $database->select($table,['url'],['is_go'=>0]);
 foreach ( $list_link_un_go as $link) {
     if (!empty($link['url'])) {

         $promise = $client->requestAsync('GET', $link['url'], ['connect_timeout' => 10]);
         yield $link['url'] => $promise;
     }
 }

}


function executorPromise(int $concurrency = 25)
{
    // init number of reject url
    $total_reject = 0;
    (new \GuzzleHttp\Promise\EachPromise(getPromise(),
        [
            'concurrency' => $concurrency,
            'fulfilled' => function (\Psr\Http\Message\ResponseInterface $response, $index) {
                getInforUrlGoingTo($response, $index);
            },
            'rejected' => function (\GuzzleHttp\Exception\TransferException $exception, $index) use (&$total_reject) {
                $total_reject++;
                dump($index . " has rejected with " . $exception->getMessage());
            },
        ]))->promise()->wait();

    return $total_reject;
}


function getInfoOfAllLinkRelativeToBaseUrl(string $base_url, int $concurrency = 25)
{

    do {
        $check = executorPromise($concurrency);
        if ($check > 0) {
            echo "\n \n================sleeping==================\n \n";
            sleep(5);
                    }

        $flag = isExistRow('is_go',0);

    } while ($flag);


}

$client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);
//url that you want to start;
$base_url = 'https://dantri.com.vn';
//set table name
$table  = 'list_url';
$data['url'] = $base_url;
$data['hash'] = md5($base_url);
$data['is_go'] = 0;
$database->insert($table,$data);



getInfoOfAllLinkRelativeToBaseUrl($base_url, 25);


