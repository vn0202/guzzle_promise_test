<?php
require "../vendor/autoload.php";


$config_db = [
    'driver' => 'mysqli',
    'host' => 'localhost',
    'database' => 'guzzle_promise',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
];
$db = new Dibi\Connection($config_db);


function isExistRow($field, $data)
{
    global  $db;
    if ($db->query("SELECT id FROM list_url where $field = ? ", $data)->fetch()) {
        return true;
    }
    return false;

}
//get all link that have existed in a link  and save to db
function getAllLinkPerUrl($crawler)
{

    global $table;
    global $db;
    global $base_url;
    $crawler->filterXPath('//body//a')->each(function (\Symfony\Component\DomCrawler\Crawler $crawler) use($base_url,$db,$table)  {


        if ($crawler->attr('href') !== '/' &&
            (str_starts_with($crawler->attr('href'), '/') ||
                str_starts_with($crawler->attr('href'), $base_url))) {
            //create full url with base_url
            $url = \Vannghia\GuzzlePromise\Libs\CrawlerHelper::makeFullUrl($base_url, $crawler->attr('href'));
            $data['url'] = $url;
            $data['hash'] = md5($url);
            $data['is_go'] = 0;

            if(!isExistRow('hash', $data['hash']))
            {
                $db->query("INSERT INTO $table ", $data);
            }

        }


    });

}


function getInforUrlGoingTo(\Psr\Http\Message\ResponseInterface $response,string  $url)
{
    global $db;
    dump("==================== go to ==================" . $url);
    $html = $response->getBody()->getContents();
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addHtmlContent($html);

    $title = $crawler->filter('title')->text();
    $hash = md5($url);

    getAllLinkPerUrl($crawler);

        dump($url);
        //update infor url
        $db->query('UPDATE list_url SET', ['is_go'=>1, 'title'=>$title], 'WHERE hash = ?',$hash );

}

function getPromise()
{
    global  $db;
    global $client;
    //get list links that have not gone into

    $list_link_un_go = $db->query('SELECT url FROM list_url WHERE is_go = ? LIMIT 1000 ', 0)->fetchAll();
    foreach ($list_link_un_go as $link) {
        if (!empty($link->url)) {
            $promise = $client->requestAsync('GET', $link->url, ['connect_timeout' => 10]);
            yield $link->url => $promise;
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
        // check if have any reject url , set sleep 5s
        $check = executorPromise($concurrency);
        if ($check > 0) {
            echo "\n \n================sleeping==================\n \n";
            sleep(5);
        }
        $flag = isExistRow('is_go',0);

    } while ($flag);


}

$client = \Vannghia\GuzzlePromise\Libs\GuzzleFactory::make([], 100);

//set table name
$table  = 'list_url';
//url that you want to start;
$base_url = 'https://dantri.com.vn';
$data['url'] = $base_url;
$data['hash'] = md5($base_url);
$data['is_go'] = 0;
$db->query('INSERT INTO list_url', $data);

getInfoOfAllLinkRelativeToBaseUrl($base_url, 25);


