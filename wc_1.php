<?php

require './WeChat.php';
define('APPID','wx83fe6ac00511b3b0');
define('APPSECRET','92c96d806d530d718fc29c1684c198b6');
$wechat = new WeChat(APPID, APPSECRET);

//获取 access_token
//$access_token = $wechat->getAccessToken();
//
//echo $access_token;

//获取二维码
//$result = $wechat->getQRCodeTicket(10000001);
//var_dump($result);
//$wechat->getQRCodeTicket(123,1);
//$wechat->getQRCodeTicket('123',1);

//$wechat->getQRCode('123','./1234.jpg',$wechat::QRCODE_TYPE_TEMP);
$wechat->getQRCode('123','',$wechat::QRCODE_TYPE_TEMP);
