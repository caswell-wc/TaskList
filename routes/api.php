<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware'=>'auth:api',
    'prefix'=>'/tasks'
], function(){
    Route::get('/', 'TaskController@index');
    Route::get('/{task}', 'TaskController@show');
    Route::post('/', 'TaskController@store');
    Route::patch('/move-subtasks', 'TaskController@moveSubTasks');
    Route::patch('/{task}/toggle-complete', 'TaskController@toggleComplete');
    Route::patch('/{task}', 'TaskController@update');
    Route::delete('/{task}', 'TaskController@delete');
});
