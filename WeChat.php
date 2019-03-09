<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/7
 * Time: 19:29
 */

class WeChat
{
    private $_appid;
    private $_appsecret;
    private $life_time = 7200;
    private $_token; // 公众平台请求开发者时需要标记

    const QRCODE_TYPE_TEMP       = 0;
    const QRCODE_TYPE_LIMIT      = 1;
    const QRCODE_TYPE_LIMIT_STR  = 2;

    public function __construct($id, $secret, $token)
    {
        $this->_appid = $id;
        $this->_appsecret = $secret;
        $this->_token = $token;
    }

    public function responseMSG() {
        $xml_str = $GLOBALS['HTTP_RAW_POST_DATA'];

        if (empty($xml_str)) {
            die('');
        }
        // 解析该xml字符串，利用simplexml
        //禁止xml实体解析，防止xml注入
        libxml_disable_entity_loader(true);
        // 从字符串获取simpleXML对象
        $request_xml = simplexml_load_string($xml_str, 'SimpleXMLElement',LIBXML_NOCDATA);

        // 判断该消息的类型通过元素：MsgType
        switch ($request_xml->MsgType) {
            case 'event';
                $event = $request_xml->Event;
                if ('subscribe' == $event) {
                    $this->_doSubScribe($request_xml);
                }
                break;
            default:
                # code...
                break;
        }
    }

    /**
     *
     * @param $request_xml
     */
    private function _doSubscribe($request_xml){
        // 利用消息发送，完成向关注用户打招呼功能
//        $text = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>';
        $text = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>';//文本回复XML模板

        $content = '感谢你关注，会向你发送优惠信息，请查收';
        $response = sprintf($text, $request_xml->FromUserName,$request_xml->ToUserName,time(),$content);
        die($response);
    }

    /**
     * 用于第一次验证URL合法性
     */
    public function firstValid() {
        // 验证签名的合法性
        if ($this->_checkSignature()) {
            // 签名合法， 告知微信公众平台服务器
            echo $_GET['echostr'];
        }
    }

    /**
     * 验证签名
     * @return bool
     */
    private function _checkSignature() {
        $signature  = $_GET["signature"];
        $timestamp  = $_GET["timestamp"];
        $nonce      = $_GET["nonce"];

        $tmp_arr  = array($this->_token, $timestamp, $nonce);
        sort($tmp_arr, SORT_STRING);
        $tmp_str  = implode( $tmp_arr );
        $tmp_str  = sha1( $tmp_str );

        if ($signature == $tmp_str) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取access_token
     * @param string $token_file 用来存储token的临时文件
     */
    public function getAccessToken($token_file = './access_token') {

        if (file_exists($token_file) && time()-filemtime($token_file) < $this->life_time) {
            return file_get_contents($token_file);
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";


        $result = $this->_requestGet($url);

        if (!$result) {
            return false;
        }

        $result_obj = json_decode($result);
        file_put_contents($token_file, $result_obj->access_token);
        return $result_obj->access_token;
    }

    /**
     * 发送GET请求
     * @param $url
     * @param bool $ssl 是否为https协议
     */
    private function _requestGet($url, $ssl = true){

        $curl = curl_init();
        // 设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
        //user_gent 请求代理信息
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        //referer头，请求来源
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        if ($ssl) {
            // 禁用后curl将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            // 检查服务器ssl证书中是否存在一个公用名(common name)
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        // 是否处理响应头
        curl_setopt($curl, CURLOPT_HEADER, false);
        // curl_exec()是否返回响应结果
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            return false;
        }
        return $response;
    }

    /**
     * @param $content
     * @param int $type QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
     * @param int $expire
     * @return bool|mixed
     */
    private function _getQRCodeTicket($content, $type = 2, $expire = 604800){
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";

        $type_list = array(
            self::QRCODE_TYPE_TEMP => 'QR_SCENE',
            self::QRCODE_TYPE_LIMIT => 'QR_LIMIT_SCENE',
            self::QRCODE_TYPE_LIMIT_STR => 'QR_LIMIT_STR_SCENE',
        );

        $access_name = $type_list[$type];
        //        $data = '{"expire_seconds": 604800, "action_name": "'. $access_name .'", "action_info": {"scene": {"scene_id": "'. $content .'"}}}';

//        {"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
//        {"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
//        {"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}
        $data_arr = [];
        switch ($type) {
            case self::QRCODE_TYPE_TEMP:
                $data_arr['expire_seconds'] = $expire;
                $data_arr['action_name'] = $access_name;
                $data_arr['action_info']['scene']['scene_id'] = $content;
                break;
            case self::QRCODE_TYPE_LIMIT:
                $data_arr['action_name'] = $access_name;
                $data_arr['action_info']['scene']['scene_id'] = $content;
                break;
            case self::QRCODE_TYPE_LIMIT_STR:
                $data_arr['action_name'] = $access_name;
                $data_arr['action_info']['scene']['scene_str'] = $content;
                break;
        }
        $data = json_encode($data_arr);
        $result = $this->_requestPost($url, $data);
        if (!$result) {
            return false;
        }

        $result_obj = json_decode($result);
        return $result_obj->ticket;
    }

    public function getQRCode($content, $file=NULL, $type=2, $expire=604800){

        $ticket = $this->_getQRCodeTicket($content, $type, $expire);
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
        $result = $this->_requestGet($url);
        if ($file) {
            file_put_contents($file, $result);
        } else {
            header('Content-Type:image/jpg');
            echo $result;
        }
    }

    private function _requestPost($url, $data, $ssl= true){
        $curl = curl_init();
        // 设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
        //user_gent 请求代理信息
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        //referer头，请求来源
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        if ($ssl) {
            // 禁用后curl将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            // 检查服务器ssl证书中是否存在一个公用名(common name)
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        // 处理post相关选项
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // 是否处理响应头
        curl_setopt($curl, CURLOPT_HEADER, false);
        // curl_exec()是否返回响应结果
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            return false;
        }
        return $response;
    }

}