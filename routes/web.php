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
    return view('paypal');
});
Route::group(['namespace'=>'Home'], function(){
    Route::get('paypal', 'PaypalController@paypal');
    Route::post('pay', 'PaypalController@pay');
    Route::get('redirect', 'PaypalController@redirect');
});