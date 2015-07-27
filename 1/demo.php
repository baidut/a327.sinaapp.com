<?php
include "wechat.class.php";
$options = array(
		'token'=>'Yingzhenqiang', //填写你设定的key
        'encodingaeskey'=>'wNSIaxzSVeIFtElg1zU6JopyjSYk1e6l1CTXEoX6x3i' //填写加密用的EncodingAESKey，如接口为明文模式可忽略
	);
$weObj = new Wechat($options);
$weObj->valid();//明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
$type = $weObj->getRev()->getRevType();
switch($type) {
	case Wechat::MSGTYPE_TEXT:
			$weObj->text("hello, I'm wechat")->reply();
			exit;
			break;
	case Wechat::MSGTYPE_EVENT:
			break;
	case Wechat::MSGTYPE_IMAGE:
			break;
	default:
			$weObj->text("help info")->reply();
}