<?php

require __DIR__ . "/autoload.php";

use Overtrue\Wechat\Server;
use Overtrue\Wechat\Message;

// url http://a327.sinaapp.com/wechat.php
// url http://a327.sinaapp.com/wechat.php

$appId          = 'wxa4616351a90ec92c'; // ��˽ ��Ҫע��
// appSecret 5f443e42d2778b66f910915f5a78e84f 
$token          = 'Yingzhenqiang'; 
$encodingAESKey = 'wNSIaxzSVeIFtElg1zU6JopyjSYk1e6l1CTXEoX6x3i'; // ��ѡ

// $encodingAESKey ����Ϊ��
$server = new Server($appId, $token, $encodingAESKey);

// ��������֤ ��֤һ�μ���
echo $server->serve();
return;

// ������������
$server->on('message', function($message) {
    return Message::make('text')->content('���ã�');
});

// ����ָ������
$server->on('message', 'image', function($message) {
    return Message::make('text')->content('�����Ѿ��յ������͵�ͼƬ��');
});

$result = $server->serve();
echo $result;