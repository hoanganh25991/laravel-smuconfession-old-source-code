<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});
Route::get('/signup', function(){
	// to sign up form
});
Route::get('{slug}/{postid?}', function($slug,$postid=null){
	echo $slug;
	echo $postid;
	$postid = (int) $postid;
	if($postid==null){
		//route to main view
	} elseif($postid==0) {
		//route to admin view
	} elseif($postid>0){
		//route to individual post view
	} else {
		App::abort(404);
	}
})->where('postid', '[0-9]+');