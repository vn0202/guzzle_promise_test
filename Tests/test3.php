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
function is_exist($field, $data)
{
    global  $database;
    if ($database->count('list_url',[$field=>$data]) == 0) {
        return false;
    }
    return true;

}
function get_all_url_per_link($crawler)
{
    global $database;
    global $base_url;
    $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $dom) use ($base_url, $database) {
        if ($dom->attr('href') !== '/' &&
            (str_starts_with($dom->attr('href'), '/') ||
                str_starts_with($dom->attr('href'), $base_url))) {
            $url = \Vannghia\GuzzlePromise\Libs\CrawlerHelper::makeFullUrl($base_url, $dom->attr('href'));
            $data['url'] = $url;
            $data['hash'] = md5($url);
            $data['is_go'] = 0;
            if(!is_exist('hash', $data['hash']))
            {
                $database->insert('list_url', $data);
            }

        }


    });

}


function getInforUrl(\Psr\Http\Message\ResponseInterface $response, $url)
{
    global $database;
    dump("==================== go to ==================" . $url);
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);
    $result['url'] = $url;
    $result['title'] = $crawler->filter('title')->text();
    $result['hash'] = md5($url);
    $result['is_go'] = 1;

    get_all_url_per_link($crawler);

    dump($result);
    $database->update('list_url',['is_go'=>1, 'title'=>$result['title']],['hash'=>$result['hash']]);

}

function getPromise()
{
    global  $db;
    global  $database;
    $client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);

 $list_link = $database->select('list_url',['url'],['is_go'=>0]);
 foreach ( $list_link as $link) {
     if (!empty($link['url'])) {

         $promise = $client->requestAsync('GET', $link['url'], ['connect_timeout' => 10]);
         yield $link['url'] => $promise;
     }
 }

}


function getAllInfor(int $concurrency = 25)
{
    $total_reject = 0;
    (new \GuzzleHttp\Promise\EachPromise(getPromise(),
        [
            'concurrency' => $concurrency,
            'fulfilled' => function (\Psr\Http\Message\ResponseInterface $response, $index) {
                getInforUrl($response, $index);
            },
            'rejected' => function (\GuzzleHttp\Exception\TransferException $exception, $index) use (&$total_reject) {
                $total_reject++;
                dump($index . " has rejected with " . $exception->getMessage());
            },
        ]))->promise()->wait();

    return $total_reject;
}


function getInforAllLink(string $base_url, int $concurrency = 25)
{
    global $db;
    do {
        $check = getAllInfor(25);
        if ($check > 0) {
            echo "\n \n================sleeping==================\n \n";
            sleep(5);
                    }

        $flag = is_exist('is_go',0);

    } while ($flag);


}

$base_url = 'https://dantri.com.vn';
$data['url'] = $base_url;
$data['hash'] = md5($base_url);
$data['is_go'] = 0;
$database->insert('list_url',$data);



getInforAllLink('https://dantri.com.vn', 25);


