<?php
require "../vendor/autoload.php";




use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'guzzle_promise',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();




function isExistRow($field, $value)
{
    global $table ;
    if (Capsule::table($table)->where($field,'=',$value)->exists()) {
        return true;
    }
    return false;

}
function getAllLinkPerUrl($crawler)
{

    $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $dom) {

        global $base_url;
        global $table;
        if ($dom->attr('href') !== '/' &&
            (str_starts_with($dom->attr('href'), '/') ||
                str_starts_with($dom->attr('href'), $base_url))) {
            $url = \Vannghia\GuzzlePromise\Libs\CrawlerHelper::makeFullUrl($base_url, $dom->attr('href'));
            $data['url'] = $url;
            $data['hash'] = md5($url);
            $data['is_go'] = 0;
            if(!isExistRow('hash', $data['hash']))
            {
              Capsule::table($table)->insert($data);
            }

        }


    });

}


function getInforUrlGoingTo(\Psr\Http\Message\ResponseInterface $response, $url)
{
    global $table;

    dump("==================== go to ==================" . $url);
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);
    $title = $crawler->filter('title')->text();
    $hash = md5($url);

    getAllLinkPerUrl($crawler);



    Capsule::table($table)->where('hash', $hash)->update( ['is_go'=>1, 'title'=>$title]);

}

function getPromise()
{
 global $client;
 global $table ;
    $list_link_un_go = Capsule::table($table)->select('url')->limit(100)->pluck('url');
    foreach ($list_link_un_go as $link) {
        if (!empty($link)) {
            $promise = $client->requestAsync('GET', $link, ['connect_timeout' => 10]);
            yield $link => $promise;
        }
    }
}


function executorPromise(int $concurrency = 25)
{
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

Capsule::schema()->dropIfExists('list_url');
Capsule::schema()->create('list_url', function ($table) {

    $table->increments('id');
    $table->string('url');
    $table->string('title')->nullable();
    $table->string('hash')->index();
    $table->integer('is_go')->default(0);
    $table->timestamps();
});
$client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);
$base_url = "https://vnexpress.net";
$table = 'list_url';
$data['url'] = $base_url;
$data['hash'] = md5($base_url);
$data['is_go'] = 0;
Capsule::table('list_url')->insert($data);




getInfoOfAllLinkRelativeToBaseUrl($base_url, 25);


