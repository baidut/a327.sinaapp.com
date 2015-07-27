<?php

require __DIR__ . "/autoload.php";

use Overtrue\Wechat\Server;
use Overtrue\Wechat\Message;

// url http://a327.sinaapp.com/wechat.php
// url http://a327.sinaapp.com/wechat.php

$appId          = 'wxa4616351a90ec92c'; // 隐私 需要注意
// appSecret 5f443e42d2778b66f910915f5a78e84f 
$token          = 'Yingzhenqiang'; 
$encodingAESKey = 'wNSIaxzSVeIFtElg1zU6JopyjSYk1e6l1CTXEoX6x3i'; // 可选

// $encodingAESKey 可以为空
$server = new Server($appId, $token, $encodingAESKey);

// 服务器验证 验证一次即可
echo $server->serve();
return;

// 监听所有类型
$server->on('message', function($message) {
    return Message::make('text')->content('您好！');
});

// 监听指定类型
$server->on('message', 'image', function($message) {
    return Message::make('text')->content('我们已经收到您发送的图片！');
});

$result = $server->serve();
echo $result;