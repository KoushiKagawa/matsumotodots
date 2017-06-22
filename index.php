<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

//アクセストークンを使いCurlHTTPCClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

//CurlHTTPClientとシークレットを使いLINE BOTをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

//LINE Message API がリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//ここからぐるなびとの連携
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app->post('/callback', function (Request $request) use ($app) {
    $body = json_decode($request->getContent(), true);
    foreach ($body['result'] as $msg) {
        // get from and message
        $from = $msg['content']['from'];
        $message = $msg['content']['text'];
        // gnavi API
        $restaurant = gnavi($message);
        if (empty($restaurant)) {
            $res_content = $msg['content'];
            $res_content['text'] = "検索結果がなかったわ・・。場所を変えてみて。";
            reply_message($from, $res_content);
        } else {
            $res_content = $msg['content'];
            $reply_msg_header = "おすすめの蕎麦屋さんを" . count($restaurant) . "件探してきたわ。" . "\n\n";
            $res_content['text'] = $reply_msg_header;
            for ($i = 0; $i < count($restaurant); $i++) {
                if ($i != count($restaurant) - 1) {
                    $res_content['text'] = $res_content['text'] . "◆" . $restaurant[$i]->name . " " . $restaurant[$i]->url . "\n\n";
                } else {
                    $res_content['text'] = $res_content['text'] . "◆" . $restaurant[$i]->name . " " . $restaurant[$i]->url;
                }
            }
            reply_message($from, $res_content);
        }
    }
    return 'OK';
});

$app->run();

function gnavi($area_name) {
    $uri = 'http://api.gnavi.co.jp/RestSearchAPI/20150630/';
    $acckey = 'your API key';
    $format = 'json';
    $keyword = '蕎麦';
    $per_page = '5';
    $url  = sprintf("%s%s%s%s%s%s%s%s%s%s%s", $uri, "?format=", $format, "&keyid=", $acckey, "&address=", $area_name, "&freeword=", $keyword, "&hit_per_page=", $per_page);

    $json = file_get_contents($url);
    $obj  = json_decode($json);

    return $obj->rest;
}

function reply_message($from, $res_content) {
    $client = new GuzzleHttp\Client();
    $requestOptions = [
        'body' => json_encode([
            'to' => [$from],
            'toChannel' => 1383378250, #Fixed value
            'eventType' => '138311608800106203', #Fixed value
            "content" => $res_content,
        ]),
        'headers' => [
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Line-ChannelID' => getenv('LINE_CHANNEL_ID'),
            'X-Line-ChannelSecret' => getenv('LINE_CHANNEL_SECRET'),
            'X-Line-Trusted-User-With-ACL' => getenv('LINE_CHANNEL_MID'),
        ],
        'proxy' => [
            'https' => getenv('FIXIE_URL'),
        ],
    ];
    try {
        $client->request('post', 'https://trialbot-api.line.me/v1/events', $requestOptions);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}


?>
