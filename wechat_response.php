<?php

header('Content-Type: text/html; charset=utf8');

require './WeChat.php';
require './app.conf.php';

$wechat = new WeChat(APPID, APPSECRET, TOKEN);

//第一次验证
//$wechat->firstValid();

$wechat->responseMSG();