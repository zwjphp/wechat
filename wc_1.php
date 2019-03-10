<?php

require './WeChat.php';
require './app.conf.php';

$wechat = new WeChat(APPID, APPSECRET, TOKEN);

//获取 access_token
//$access_token = $wechat->getAccessToken();
//
//echo $access_token;

//获取二维码
//$result = $wechat->getQRCodeTicket(10000001);
//var_dump($result);
//$wechat->getQRCodeTicket(123,1);
//$wechat->getQRCodeTicket('123',1);

//$wechat->getQRCode('123','',$wechat::QRCODE_TYPE_TEMP);
//$wechat->getQRCode('123','',$wechat::QRCODE_TYPE_TEMP);

$wechat->getBaseInfo();



