<?php

/**
 * LINEBOT MessagingAPIの便利クラス
 * 
 * @author kazz
 * @link https://kazzstorage.com
 */
class MessagingApi
{
    const MESSAGING_API_URL = "https://api.line.me/v2";

    private $accessToken;   // string :管理画面から発行したアクセストークン
    private $channelSecret; // string :管理画面から発行した秘密鍵
    private $messageObject; // array  :LINEから送られてきたデータ

    /**
     * @param string $accessToken
     * @param string $channelSecret
     * @param array  $messageObject
     */
    public function __construct(
        string $accessToken,
        string $channelSecret,
        array $messageObject = null
    ) {
        $this->accessToken = $accessToken;
        $this->channelSecret = $channelSecret;
        $this->messageObject = $messageObject;
    }

    /**
     * リクエストボディと送信先の検証
     * 
     * @param  string $requestBody リクエストボディ
     * @return bool   検証成功: true
     */
    public function isSignProper(string $requestBody): bool
    {
        $hash = hash_hmac("sha256", $requestBody, $this->channelSecret, true);
        $signature = base64_encode($hash);
        $compSignature = $_SERVER["HTTP_X_LINE_SIGNATURE"];
        return $signature === $compSignature;
    }

    /**
     * アクションの種別がマッチしているかを調べる
     * 例: message, message/text, message/image, follow
     *  
     * @param string $event 判定するイベントタイプ
     * @return bool マッチした: true
     */
    public function eventMatched(string $event): bool
    {
        $messageEvent = $this->messageObject["events"][0];

        switch ($messageEvent["type"]) {
            case "// テキストメッセージが送られてきたときのイベント":
                if ($event === "message") return true;
                $messageType = $messageEvent["message"]["type"];
                switch ($messageType) {
                    case "text":
                        return $event === "message/text";
                    case "image":
                        return $event === "message/image";
                    case "video":
                        return $event === "message/video";
                    case "user":
                        return $event === "message/user";
                    case "audio":
                        return $event === "message/audio";
                    case "file":
                        return $event === "message/file";
                    case "location":
                        return $event === "message/location";
                    case "sticker":
                        return $event === "message/sticker";
                    default:
                        return false;
                }
            case "follow":
                return $event === "follow";
            case "unfollow":
                return $event === "unfollow";
            case "join":
                return $event === "join";
            case "leave":
                return $event === "leave";
            case "memberJoined":
                return $event === "memberJoined";
            case "memberLeft":
                return $event === "memberLeft";
            case "postback":
                return $event === "postback";
            case "beacon":
                return $event === "beacon";
            case "accountLink":
                return $event === "accountLink";
            case "things":
                return $event === "things";
            default:
                return false;
        }
    }

    /**
     * messagesの内容をクライアントに送信
     * 注: 仕様上、１つのreplyTokenにつき１回のみ
     * 
     * @param  array $messages 送信するメッセージ
     * @return bool 送信成功(ステータスコード200): true
     */
    public function sendReply(array $messages): bool
    {
        $url = self::MESSAGING_API_URL . "/bot/message/reply";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->accessToken
        ];
        $body = json_encode([
            "replyToken" => $this->messageObject["events"][0]["replyToken"],
            "messages"   => $messages
        ]);

        list($binaryContent, $httpStatusCode) = $this->httpPostRequest($url, $headers, $body);

        return $httpStatusCode === 200;
    }

    /**
     * 送信されたコンテンツ(画像、動画、音声など)を取得し返す
     * 
     * @return $binaryContent コンテンツ(バイナリ)
     */
    public function getBineryContent(): string
    {
        $messageId = $this->messageObject["events"][0]["message"]["id"];
        $url = self::MESSAGING_API_URL . "/bot/message/{$messageId}/content";
        $headers = [
            "Authorization: Bearer " . $this->accessToken
        ];

        list($binaryContent, $httpStatusCode) = $this->httpGetRequest($url, $headers);

        return $binaryContent;
    }

    /**
     * 特定のクライアントにPUSHメッセージ送信
     * 
     * @param array $messages 送信するメッセージ
     * @param array $to 送信先UserID配列
     * @return bool 送信成功(ステータスコード200):true
     */
    public function sendPushMessage(array $messages, array $to): bool
    {
        $url = self::MESSAGING_API_URL . "/bot/message/multicast";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->accessToken
        ];
        $body = json_encode([
            "to" => $to,
            "messages" => $messages
        ]);

        list($binaryContent, $httpStatusCode) = $this->httpPostRequest($url, $headers, $body);

        return $httpStatusCode === 200;
    }

    /**
     * 全クライアントにPUSHメッセージ送信
     * 
     * @param array $messages 送信するメッセージ
     * @return bool 送信成功(ステータスコード200):true
     */
    public function sendBroadcastMessage(array $messages): bool
    {
        $url = self::MESSAGING_API_URL . "/bot/message/broadcast";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->accessToken
        ];
        $body = json_encode([
            "messages" => $messages
        ]);

        list($binaryContent, $httpStatusCode) = $this->httpPostRequest($url, $headers, $body);

        return $httpStatusCode === 200;
    }

    /**
     * ユーザープロフィール取得
     * 
     * @param string $userId ユーザーID
     * @return array $userProfile ユーザープロフィール
     */
    public function getUserProfile(string $userId): array
    {
        $url = self::MESSAGING_API_URL . "/bot/profile/{$userId}";
        $headers = [
            "Authorization: Bearer " . $this->accessToken
        ];

        list($userProfile, $httpStatusCode) = $this->httpGetRequest($url, $headers);

        return json_decode($userProfile, true);
    }

    /**
     * GETリクエスト送信
     * 
     * @param string $url 宛先URL
     * @param array  $headers リクエストヘッダ
     * @return array [$response レスポンスボディ, $httpStatusCode ステータスコード]
     */
    protected function httpGetRequest(string $url, array $headers): array
    {
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return [$response, $httpStatusCode];
    }

    /**
     * POSTリクエスト送信
     * 
     * @param string $url 宛先URL
     * @param array  $headers リクエストヘッダ
     * @param string $body リクエストボディ
     * @return array [$response レスポンスボディ, $httpStatusCode ステータスコード]
     */
    protected function httpPostRequest(string $url, array $headers, string $body): array
    {
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return [$response, $httpStatusCode];
    }
}
