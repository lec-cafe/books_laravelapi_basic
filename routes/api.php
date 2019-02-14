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

Route::post("/auth/login",function(){
    $email = request()->get("email");
    $password = request()->get("password");

    $user = \App\User::where("email",$email)->first();
    if ($user && Hash::check($password, $user->password)) {
        $token = str_random();
        $user->token = $token;
        $user->save();
        return [
            "token" => $token,
            "user" => $user
        ];
    }else{
        abort(403);
    }
});

Route::get("/profile",function(){
    $user = Auth::guard("api")->user();
    return [
        "user" => $user
    ];
})->middleware("auth:api");

Route::get("/task/list",\App\Http\Actions\TaskListAction::class."@handle");

