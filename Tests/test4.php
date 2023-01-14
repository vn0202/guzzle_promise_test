<?php
require "../vendor/autoload.php";

use Vannghia\GuzzlePromise\CustomClient;
use Vannghia\SimpleQueryBuilder\Config\Connection;
use Vannghia\GuzzlePromise\Url;
use Illuminate\Database\Capsule\Manager as DB;


$db = new DB();

$db->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'guzzle_promise',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

DB::schema()->dropIfExists('list_url');

DB::schema()->create('list_url', function ($table) {

    $table->increments('id');
    $table->string('url');
    $table->string('title')->nullable();
    $table->string('hash');
    $table->timestamps();
});




$client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make();
$base_url = "https://dantri.com.vn";

function is_exist($field, $value)
{
    if (DB::table('list_url')->where($field,'=',$value)->count() ==0) {
        return false;

    }
    return true;

}
function get_all_url_per_link($crawler)
{

    $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $dom) {

        global $base_url;
        if ($dom->attr('href') !== '/' &&
            (str_starts_with($dom->attr('href'), '/') ||
                str_starts_with($dom->attr('href'), $base_url))) {
            $url = \Vannghia\GuzzlePromise\Libs\CrawlerHelper::makeFullUrl($base_url, $dom->attr('href'));
            $data['url'] = $url;
            $data['hash'] = md5($url);
            $data['is_go'] = 0;
            if(!is_exist('hash', $data['hash']))
            {
              DB::table('list_url')->insert($data);
            }

        }


    });

}


function getInforUrl(\Psr\Http\Message\ResponseInterface $response, $url)
{

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

    DB::table('list_url')->where('hash', $result['hash'])->update( ['is_go'=>1, 'title'=>$result['title']]);

}

function getPromise()
{
    $client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);

    $list_link = DB::table('list_url')->select('url')->limit(100)->pluck('url');
    foreach ($list_link as $link) {
        if (!empty($link->url)) {
            $promise = $client->requestAsync('GET', $link->url, ['connect_timeout' => 10]);
            yield $link->url => $promise;
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
DB::table('list_url')->insert($data);


$list_link = DB::table('list_url')->select('url')->limit(100)->pluck('url');
dd($list_link);
//getInforAllLink('https://dantri.com.vn', 25);


