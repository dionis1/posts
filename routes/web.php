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
    return view('welcome');
});

Auth::routes();


Route::group(['middleware' => 'auth'], function () {
	Route::get('/home', 'HomeController@index');

	Route::group(['prefix' => 'post'], function () {
	    
		Route::get('/', 'PostController@index');
		Route::get('/create', 'PostController@create');
		Route::post('/create', 'PostController@store');
		Route::get('/{post}/edit', 'PostController@edit');
		Route::post('/{post}/update', 'PostController@update');
		Route::get('/show/{post}', 'PostController@show');
		Route::get('/delete/{post}', 'PostController@delete');



		Route::get('/all-posts', 'PostController@dataForDataTable')->name('allPostsDataTable');
		Route::get('/user-posts', 'PostController@dataFromOneUserForDataTable')->name('userPostsDataTable');

	});
});
