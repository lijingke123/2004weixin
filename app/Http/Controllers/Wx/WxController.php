<?php

namespace App\Http\Controllers\Wx;

use App\models\WeachModel;
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
            echo  $this->custom();

            echo "";
//
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
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "index";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            $postStr = file_get_contents("php://input");
            Log::info("====" . $postStr);
            $postArray = simplexml_load_string($postStr);
            Log::info('=================================' . $postArray);
            $toUser = $postArray->FromUserName;

            if ($postArray->MsgType == "event") {
                if ($postArray->Event == "subscribe") {
                    $WeachModelInfo = WeachModel::where('openid',$toUser)->first();
                    if(is_object($WeachModelInfo)){
                        $WeachModelInfo = $WeachModelInfo->toArray();
                    }
                if (!empty($WeachModelInfo)){
                    $content = "欢迎回来";
                }else {
                    $content = "你好，欢迎关注";
                    $token = $this->token();
                    $data = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $token . "&openid=" . $toUser . "&lang=zh_CN";
                    file_put_contents('user_wetch', $data);//存文件
                    $wetch = file_get_contents($data);
                    $json = json_decode($wetch, true);
//        file_put_contents('user_wetch',$data,'FILE_APPEND');//存文件
//        die;
                    $data = [
                        'openid' => $toUser,
                        'nickname' => $json['nickname'],
                        'sex' => $json['sex'],
                        'city' => $json['city'],
                        'country' => $json['country'],
                        'province' => $json['province'],
                        'language' => $json['language'],
                        'subscribe_time' => $json['subscribe_time'],
                    ];
                    $weachInfo = WeachModel::insert($data);
                }
//                    echo  $this->custom();
                    $this->text($postArray, $content);
//                    echo $result;die;
                }
            } elseif ($postArray->MsgType == "text") {
                $msg = $postArray->Content;
                switch ($msg) {
                    case '你好':
                        $content = '嗨喽';
                        $this->text($postArray, $content);
//                        echo $result;die;
                        break;
                    case '天气':
                        $content = $this->getweather();
                        $this->text($postArray, $content);
//                        echo $result;die;
                        break;
                    case '图文':
                        $this->upload($postArray);
                }
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
        return $temple;
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


    public function custom(){
        $access_token = $this->token();
        $url = 'http://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
    //    echo $url;
        $array = [
            'button'=> [
        [
                'name'=>'菜单',
                "sub_button"=>[
                    [
                        'type'=>'view',
                        'name'=>'百度',
                        'url'=>'http://www.baidu.com'
                    ],
                    [
                        'type'=>'click',
                        'name'=>"天气",
                        'key'=>'WEATHER'
                    ],
                ]
            ]
        ]
        ];


//        $array->toArray();
//        print_r($array);exit;
        $client = new Client();
        $response = $client->request('POST',$url,[
            'verify'=>false,
            'body'=>json_encode($array,JSON_UNESCAPED_UNICODE)
        ]);
        $data = $response->getBody();
        echo $data;
    }

    public function wx(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "index";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            //接收数据
            $xml_str = file_get_contents("php://input");
            //记录日记
            file_put_contents('wx_event',$xml_str);
            //把xml转换为PHP的对象或者数组
            echo "";
//            die;
//                return true;
        }else{
//            echo '';
            return false;
        }
    }

    public function json()
    {


        $arr = [
            "name"  => "zhangsan",
            "age"   => 20,
            'email' => "zhangshan@qq.com",
            "name_cn"  => "张三"
        ];

        echo '<pre>';print_r($arr);echo '</pre>';

        echo '<hr>';
        $json = json_encode($arr,JSON_UNESCAPED_UNICODE);
        echo $json;
        echo '<hr>';
        //json 转 对象
        $obj = json_decode($json);
        echo '<pre>';print_r($obj);echo '</pre>';
        echo '<hr>';


        $arr = json_decode($json,true);
        echo '<pre>';print_r($arr);echo '</pre>';
    }
}
