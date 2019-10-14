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
// 登録者全員に送信
if ($line->sendBroadcastMessage($messages)) {
    echo "PUSH成功";
} else {
    echo "PUSH失敗";
}
// or $line->sendPushMessage($messages, ["userid"]); // 指定ユーザーに送信