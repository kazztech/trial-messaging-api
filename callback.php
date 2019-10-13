<?php
require "./MessagingApi.php";

const ACCESS_TOKEN = "SYzSBq2qH6ryDc0eOMEf05CkLulp7g7O5Mfv633eRDdyG9tJDaRzJ6KSxRL9cz39heCtXyoGxyuLmV4pHhXH3rC58QuF3kT2zJ7/OPKvocowXpO1WoAniTFtUj6gCG4ll2JhkAihA0MhfTLr5Ivu9QdB04t89/1O/w1cDnyilFU=";
const CHANNEL_SECRET = "deb65e275f7542e8d51eba7b5fd6b4c2";

$requestBody = file_get_contents("php://input");
$messageObject = json_decode($requestBody, true);

$line = new MessagingApi(ACCESS_TOKEN, CHANNEL_SECRET, $messageObject);

// 送信元の検証
if (!$line->isSignProper($requestBody))
    die("検証失敗、不正なアクセス");

// 共通プロパティ
$messageEvent = $messageObject["events"][0];

// 友だち追加orブロック解除時のイベント
if ($line->eventMatched("follow")) {
    $messages = [];
    $messages[] = [
        "type" => "text",
        "text" => "登録ありがとう！"
    ];
    $line->sendReply($messages);
}

// テキストが送られてきたときのイベント
if ($line->eventMatched("message/text")) {
    $replyText = $messageEvent["message"]["text"] . "とは？";
    $messages = [];
    $messages[] = [
        "type" => "text",
        "text" => $replyText
    ];
    $line->sendReply($messages);
}

// 画像が送られてきたときのイベント
if ($line->eventMatched("message/image")) {
    // 送信された画像のバイナリを取得
    $contentBinary = $line->getBineryContent();

    // 取得した画像データを保存
    $imgPath = "images/" . $messageEvent["message"]["id"] . ".jpg";
    file_put_contents($imgPath, $contentBinary);

    // メッセージ送信
    $messages = [];
    $messages[] = [
        "type" => "image",
        "originalContentUrl" => "https://kazzstorage.com/linebot/{$imgPath}",
        "previewImageUrl" => "https://kazzstorage.com/linebot/{$imgPath}"
    ];
    $messages[] = [
        "type" => "text",
        "text" => "これ返すよ"
    ];
    $line->sendReply($messages);
}
