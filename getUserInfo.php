<?php
require './WeChat.php';
require './app.conf.php';

$wechat = new WeChat(APPID, APPSECRET, TOKEN);

$wechat->getUserOpenId();

?>