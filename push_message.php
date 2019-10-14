<?php // PUSHメッセージ送信例
require "./MessagingApi.php";

// MessagingAPIの設定画面から発行されるもの
const ACCESS_TOKEN = "";
const CHANNEL_SECRET = "";

$line = new MessagingApi(ACCESS_TOKEN, CHANNEL_SECRET);

// 送信
$messages = [];
$messages[] = [
    "type" => "text",
    "text" => "PUSH!!!"
];
$line->sendPushMessage($messages, ["userid"]);
// or $line->sendBroadcastMessage($messages); // 登録者全員に送信