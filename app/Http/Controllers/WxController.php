<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxController extends Controller
{
    public function index(){
        $echostr = request()->get('echostr','');
        if($this->checkSignature() && !empty($echostr)){
            echo $echostr;
        }
    }
    private function checkSignature()
{
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];
	
    $token = "index";
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
}
