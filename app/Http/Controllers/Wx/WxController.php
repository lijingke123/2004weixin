<?php

namespace App\Http\Controllers\Wx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class WxController extends Controller
{
    public function   index(){
        $res = request()->get('echostr','');
        if($this->checkSignature() && !empty($res)){
            echo $res;
        }else{
            $res = $this->getAccesstoken();
//            dd($res);
            $xml = file_get_contents("php://input");
            file_get_contents("data.txt",$xml);
            
        }

    }
    private function checkSignature()
{
    $signature = request()->get("signature");
    $timestamp = request()->get("timestamp");
    $nonce = request()->get("nonce");
	
    $token = "test";
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    
    if( $tmpStr == $signature ){
        return true;
    }else{
        return false;
    }
}
    public function getAccesstoken(){
        $key = 'weiAccess_token';
        if(!Redis::get($key)){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx8b14088e2da627cf&secret=10a2cada2130e0ada2e6e476ad16d448";
            $token = file_get_contents($url);
//            dd($token);
            $token = json_decode($token,true);

            if(isset($token['access_token'])){
                $token = $token['access_token'];
                Redis::setex($key,3600,$token);
                return "无缓存".$token;
            }else{
                return false;
            }
        }else{
            return "有缓存".Redis::get($key);
        }
    }
}
