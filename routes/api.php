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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get("/tasks",function(){
    return [
        "status" => "OK",
        "tasks" => \App\Task::all(),
    ];
});

Route::post("/task",function(){
    $task = new \App\Task();
    $task->name = request()->get("name");
    $task->save();
    return [];
});

Route::delete("/task/{id}",function($id){
    $task = \App\Task::find($id);
    if($task){
        $task->delete();
    }
    return [];
});
