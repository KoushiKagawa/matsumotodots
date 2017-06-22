<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

//アクセストークンを使いCurlHTTPCClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

//CurlHTTPClientとシークレットを使いLINE BOTをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

//LINE Message API がリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//署名が不当かチェック。正当であればリクエストをパースし配列へ
//不正であれば例外の内容を出力
try{
  $events = $bot->parseEventRequest(file_get_contents('php://input'),$signature);
}catch(\LINE\LINEBot\Exception\InvalidSignatureException $e){
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));
}catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e){
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));
}catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e){
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));
}catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e){
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}

//配列に格納された各イベントをループで処理
foreach ($events as $event) {
  //MessageEventクラスのインスタンスでなければ処理をスキップ
  if(!($event instanceof \LINE\LINEBot\Event\MessageEvent)){
    error_log('Non message event has come');
    continue;
  }
  //textMessageクラスのインスタンスでなければ処理をスキップ
  if(!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)){
    error_log('Non text message has come');
    continue;
  }
  //オウム返し
  $bot->replyText($event->getReplyToken(), $event->getText());
}



//テキストを返信。引用はLinebot、返信先、テキスト
function replyTextMessage($bot, $replyToken, $text){
  //返信を行いレスポンスを取得
  //TextMessageBuilderの引用はテキスト
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  //レスポンスが異常な場合
  if(!$response->isSucceeded()){
    //エラー内容を出力
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }
}

//画像を返信。引数はLINEBot、返信先、画像URL、サムネイルURL
function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl){
  //ImageMessageBuilderの引数は画像URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl,$previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus . '' .$response->getRawBody);
  }
}

//位置情報を返信。引数はLINEBot、返信先、タイトル、住所、緯度、経度
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon){
  //LocationMessageBuilderの引数はダイアログのタイトル、住所、緯度、経度
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title,$address,$lat,$lon));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }
}

//スタンプを返信。引数はLINEBot、返信先、スタンプのパッケージID、スタンプID
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId){
  //StickerMessageBuilderの引数は、スタンプのパッケージID,スタンプID
  $response = $bot->replyMessage($replyToken,new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }
}

//動画を返信。引数はLINEBot、返信先、動画URL、サムネイルURL
function replyVideoMessage($bot, $replyToken, $originalContentURL, $previewImageUrl){
  //VideoMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder($originalContentURL,$previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }
}

//オーディオファイルを返信。引数はLINEBot、返信先、ファイルのURL、ファイルの再生時間
function replyAudioMessage($bot, $replyToken, $originalContentUrl, $audioLength){
  //AudioMessageBuilderの引数はファイルのURL、ファイルの再生時間、
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentURL,$audioLength));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }
}

//複数のメッセージをまとめて返信。引きづ雨はLINEBot、返信先、メッセージ（可変長引数）
function replyMultiMessage($bot, $replyToken, ...$msgs){
  //MultiMessageBuilderをインスタンス化
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  //ビルダーにメッセージを全て追加
  foreach($msgs as $value){
    $builder->add($value);
  }
  $response = $bot->replyMessage($replyToken, $builder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }

}

//Button テンプレートを返信。引数はLINEBot、返信先、代替テキスト
//画像URL、タイトル、本文、アクション(可変長引数)
function replyButtonsTemplate($bot, $replyToken, $alternativeText, $ImageUrl, $title, $text, ...$actions){
  //アクションを格納する配列
  $actionArray = array();
  //アクションを全て追加
  foreach($actions as $value){
    array_push($actionArray, $value);

    if($event instanceof \LINE\LINEBot\Event\PostbackEvent){
      //テキストを返信し次のイベントの処理へ
      replyTextMessage($bot, $event->getReplyToken(), 'Postback受信「' . $event->getPostbackData() . '」');
      continue;
    }
  }
  //TemplateMessageBuilderの引数は代替テキスト、ButtonTemplateBuilder
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    //ButtonTemplateBuilderの引数はタイトル、本文、画像URL、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder($title, $text, $imageUrl, $actionArray)
     );
  $response = $bot->replyMessage($replyToken, $builder);
    if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .$response->getRawBody());
  }

}


// Confirmテンプレーとを返信。引数はLINEBot、返信先、代替テキスト、本文、アクション（可変長引数）
function replyConfirmTemplate($bot, $replyToken, $alternativeText, $text, ...$actions){
  $actionArray = array();
  foreach ($actions as $value) {
    array_push($actionArray, $value);
  }
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    //Confirmテンプレートの引数はテキスト、アクション配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder($text, $actionArray)
    );
  $response = $bot->replyMessage($replyToken, $builder);
  if(!$response->isSucceeded()){
    error_log('Failed!'.$response->getHTTPStatus . '' . $response->getRawBody());
  }
}

// Carousel テンプレートを返信。引数はLINEBot、返信先、代替テキスト、ダイアログの配列
function replyCarouselTemplate($bot, $replyToken, $alternativeText, $columnArray){
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    //Carouselテンプレートの引数はダイアログ配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder(
      $columnArray)
  );
  $response = $bot->replyMessage($replyToken,$builder);
  if(!$response->isSucceeded()){
    error_log('Failed!'.$response->getHTTPStatus . '' . $response->getRawBody());
  }
}


?>
