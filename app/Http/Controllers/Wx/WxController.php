<?php

namespace App\Http\Controllers\Wx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WxController extends Controller
{
    //
    public function index()
    {
//        $echostr = request()->get('echostr','');
//        if($this->checkSignature() && !empty($echostr)){
//            echo $echostr;
//        }
        $this->sub();
    }


    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "index";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            $xml_str = file_get_contents("php://input");
            //记录日记
            file_put_contents('we_event', $xml_str);
            //把xml转换为PHP的对象或者数组
            echo "";
//            die;
        } else {
//            echo '';
            return false;
        }
    }


    public function token()
    {
        $key = 'wx:access_token';
        $token = Redis::get($key);
        if ($token) {
            echo "有缓存";
            '</br>';
            echo $token;
        } else {
            echo "无缓存";
            '</br>';
            $APPID = "wx8b14088e2da627cf";
            $APPSECRET = "10a2cada2130e0ada2e6e476ad16d448";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$APPID}&secret={$APPSECRET}";
//        echo $url;
            $response = file_get_contents($url);
//            dd($url);
            $data = json_decode($response, true);

//            dd($data);
            $token = $data['access_token'];
//
            dd($token);
            Redis::set($key, $token);
            Redis::expire($key, 3600);

        }
        echo "access_token:" . $token;
    }


    public function sub()
    {
        $postStr = file_get_contents("php://input");
        Log::info("====" . $postStr);
        $postArray = simplexml_load_string($postStr);
        Log::info('=================================' . $postArray);
        if ($postArray->MsgType == "event") {
            if ($postArray->Event == "subscribe") {
                $content = "你好，欢迎关注";
                $this->text($postArray, $content);
            }
        } elseif ($postArray->MsgType == "text") {
            $msg = $postArray->Content;
            switch ($msg) {
                case '你好':
                    $content = '嗨喽';
                    $this->text($postArray, $content);
                    break;
                case '天气':
                    $content = $this->getweather();
                    $this->text($postArray, $content);
                    break;
                case '图文':
                    $this->upload($postArray);
            }
        }
    }


//关注回复xml
    public function text($postArray, $content)
    {
        $temple = '<xml>
          <ToUserName><![CDATA[' . $postArray->FromUserName . ']]></ToUserName>
          <FromUserName><![CDATA[' . $postArray->ToUserName . ']]></FromUserName>
          <CreateTime>' . time() . '</CreateTime>
          <MsgType><![CDATA[text]]></MsgType>
          <Content><![CDATA[' . $content . ']]></Content>
        </xml>';
        echo $temple;
    }


//天气预报
    public function getweather()
    {
        $url = 'http://api.k780.com:88/?app=weather.future&weaid=heze&&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';
        $weather = file_get_contents($url);
        $weather = json_decode($weather, true);

        if ($weather['success']) {
            $content = '';
            foreach ($weather['result'] as $v) {
                $content .= '日期：' . $v['days'] . $v['week'] . ' 当日温度：' . $v['temperature'] . ' 天气：' . $v['weather'] . ' 风向：' . $v['wind'];
            }
        }

        Log::info('====' . $content);
        return $content;
    }

    //图文回复
    public function upload($postArray)
    {
        $url = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1605333685&di=8a332fbca6c6914f1a03b7e97d7f03e4&imgtype=jpg&er=1&src=http%3A%2F%2Fpic1.win4000.com%2Fwallpaper%2F2018-07-25%2F5b583e427ac9f.jpg';
        $curl = 'www.jd.com';
        $title = '哈哈哈';
        $description = '随便';

        $uploads = '<xml>
                          <ToUserName><![CDATA[' . $postArray->FromUserName . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $postArray->ToUserName . ']]></FromUserName>
                          <CreateTime>12345678</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA[' . $title . ']]></Title>
                              <Description><![CDATA[' . $description . ']]></Description>
                              <PicUrl><![CDATA[' . $url . ']]></PicUrl>
                              <Url><![CDATA[' . $curl . ']]></Url>
                            </item>
                          </Articles>
                        </xml>';
        echo $uploads;
    }
}