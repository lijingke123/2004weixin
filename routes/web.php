<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    echo phpinfo();
    return view('welcome');
});
Route::any('/index','Wx\WxController@index');
Route::any('/token','Wx\WxController@token');
Route::any('','Wx\WxController@wx');
Route::any('/custom','Wx\WxController@custom');


//TEST  路由分组
Route::prefix('/test')->group(function (){
    Route::get('/json',"Wx\WxController@json");
    Route::get('/guzz1',"WxController@guzzle1");
    Route::get('/guzz2',"WxController@guzzle2");
    Route::get('/guzz3',"WxController@guzzle3");
});
