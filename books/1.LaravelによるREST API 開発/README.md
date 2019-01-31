---
title: Laravelによる REST API 開発
---

# Laravelによる REST API 開発

REST API は HTTP 通信を利用してデータの受け渡しを実現するためのシンプルなWebシステムの形です。

通常の Laravel アプリケーションのように View を用いて HTML を生成するのではなく、
データのやり取りは単純なJSON の形式で行われます。

Webシステムを、REST API 形式で構築することにより、フロントエンドのWebアプリケーションや、iOS / Android などの ネイティブアプリケーションなど
様々な場面で Laravel を用いた データベースシステムを活用する事ができるようになります。

まずははじめに、シンプルなタスクリストの API を作成して、
REST API 形式の Webシステムを構築するフローを確認してみましょう。

## タスクリストAPIの設計

REST API は Request と Response でその形式を設計します。

一般的なタスクリストの機能を REST API で実現するには、
以下のような API が必要になるでしょう

- `GET /tasks` タスク一覧を取得するAPI 
- `POST /task` タスクを作成するAPI
- `DELETE /task/{id}` 完了したタスクを削除するAPI


上記の API を作成するために、まずはテーブルを作成してテストデータを作成し、
開発の準備を整えましょう。

### テーブルの作成

まずはテーブルを作成しましょう。
マイグレーションコマンドを実行して、マイグレーションファイルを作成します。

```bash
$ php artisan make:migration todo
```

作成されたマイグレーションファイルに以下のようなタスクテーブル用のDB定義を記述します。

```php
public function up(){
    //テーブル作成
    Schema::create('tasks', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });
}

public function down(){
    //テーブル削除
    Schema::dropIfExists('tasks');
}
```

マイグレーションの準備ができたらmigrateコマンドを実行して完了です。

```bash
$ php artisan migrate
```

### Eloquent の準備

タスクを投入するための、Eloquent Model を作成しましょう。

`app/Task.php` を作成し以下のように記述します。

```
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = "tasks";

}
```

次に、Eloquent を利用して Seeder を作成します。

`database/seeds/DatabaseSeeder.php` の `run` メソドを以下のような形で作成します。

```php
    public function run()
    {
        \App\Task::create(["name" => "牛乳を買う"]);
        \App\Task::create(["name" => "本を読む"]);
        \App\Task::create(["name" => "部屋を掃除する"]);
        // $this->call(UsersTableSeeder::class);
    }
```

シーダを以下のコマンドで実行して、データベース内にデータを投入しましょう。

```bash
$ php artisan db:seed
```

## REST API の作成

今回作成するAPIは以下の 3点です。

- `GET /tasks` タスク一覧を取得するAPI 
- `POST /task` タスクを作成するAPI
- `DELETE /task/{id}` 完了したタスクを削除するAPI

API に関する情報はすべて `routes/api.php` に記述します。
一覧取得のAPIから順に API を作成していきましょう。

::: warning
`routes/web.php` に REST API を記述した場合、POST などの HTTP メソドを利用した際に
CSRF トークンが必要となり、 REST APIが非常に利用しづらくなります。
:::


### タスク一覧を取得するAPI

タスクの一覧を取得する API を作成する場合、
`routes/api.php` に以下の様に記述します。

```php
<?php
Route::get("/tasks",function(){
    return \App\Task::all();
});
```

ブラウザで `/api/tasks` にアクセスすると、JSON 形式でタスクの一覧が表示されるのが確認できるでしょう。

::: tip
`routes/api.php` に記述した API 定義のURLには自動的に `/api` のプレフィックスが付与されます。
この挙動は、後述する REST APIの設定 にて変更する事が可能です。 
:::

Laravel では、 配列 `[]` や Eloquent のコレクションを return にわたすことで、
自動的に JSON 形式に変換されて REST API として機能する様に動作します。

JSON 形式への変換は再帰的に行われるため、以下のように任意の配列構造を作成することもできます。

```php
<?php
Route::get("/tasks",function(){
    return [
        "status" => "OK",
        "tasks" => \App\Task::all(),
    ];
});
```

### タスクを追加するAPI

タスクを追加するAPIは、以下のようなかたちで POST の API として定義します。

```php
<?php
Route::post("/task",function(){
    $task = new \App\Task();
    $task->name = request()->get("name");
    $task->save();
    return [];
});
```

`request()->get("name")` で REST API から送信された追加するタスクの名前を取得します。

POST 形式の API はブラウザから叩くのが難しいため、 
Postman などのクライアントツールを利用して API を発行してみましょう。

[Postman \| Download Postman App](https://www.getpostman.com/downloads/)

REST API の Request データは JSON 形式で、例えば以下のようにして送信します。

```json
{
    "name": "靴を買いに行く"
}
```

### タスクを削除するAPI

通常 Webページを利用するシステムでは GET / POST の HTTP メソドがよく用いられますが、
REST API では PATCH / PUT / DELETE などの API も利用することが可能です。

タスクを削除する場合には DELETE メソドを利用して、以下のように記述します。

```php
<?php
Route::delete("/task/{id}",function($id){
    $task = \App\Task::find($id);
    if($task){
        $task->delete();
    }    
    return [];
});
```

## REST API の外部設計

REST API の構造は、リクエストとレスポンスに分けて考える事ができます。

### レスポンス

一般的に API のレスポンスは、JSON 形式で送信されます。

Laravel では ルートの処理内で、return 文を用いて 配列を返した場合、自動的に配列が JSON形式に変換されて レスポンスとして生成されます。

```php
<?php
Route::get("status", function(){
    return [
      "status" => "OK"
      "message" => "no issues with systemn"
    ];
});
```

REST API のレスポンスは、
メインの情報である ResponseBodyの他に、
ステータスコードやヘッダといった補足的な情報を添えることができます。

- ステータスコード： API の結果の形式を、数値で表す
- レスポンスヘッダ： API の補足的な情報を表現する

ステータスコードは、HTTP通信においては、成功なら `200`  のステータスコードとなりますが、
データが存在しない場合には `404` システムエラーの場合には `503` を返すのが好ましいケースもあります。

レスポンスヘッダは、API 通信における補足的な返却情報です。
例えばユーザの一覧を返却する API では、
レスポンスボディで JSON 形式のユーザリストを返却し、
ページングの情報を レスポンスヘッダで返す、などといった使われ方をするケースもあります。

ステータスコードやレスポンスヘッダを ルート内で定義する場合には以下のように `response` 関数を利用して レスポンスを作成します。

```php
<?php
Route::get("status", function(){
    //...
    $headers = [
        "X-PAGE" => 1
    ];
    return response([],200,$headers);
});
```

### リクエスト

通信元からのアクセス情報をリクエストと呼びます。

API を構築する場合、リクエストの形式として URL だけでなく リクエストメソドについても考える必要があります。

Web ページのリクエストではでは、 GET / POST の メソドが用いられること一般的ですが、 API ではそれを拡張して以下の様なメソドが利用されます。

- GET
- POST
- PUT
- PATCH
- DELETE

任意のリクエストメソドを利用する場合には、 `Route::get` `Route::post` などのようにしてリクエストメソドを定義します。

`GET` 以外のメソドでは、リクエストボディとしてパラメータを付与する事ができます。
リクエストボディの値は JSON 形式で送信され、 `request` 関数を用いてこれを取得することができます。

```php
<?php
Route::post("/task",function(){
    $task = new \App\Task();
    $task->name = request()->get("name");
    $task->save();
    return [];
});
```

単純に リクエストボディ全体を取得したい場合には、 `request()->all()` を取得します。

```php
<?php
Route::post("/task",function(){
    $payload = request()->all();
    $task = new \App\Task();
    $task->name = $payload["name"];
    $task->save();
    return [];
});
```

また、API 通信では、認証やメタ情報を ヘッダを用いて送信することもあります。
ヘッダの値は `request()->header("hogehoge")` の形式で取得することも可能です。

## REST API の設定

`routes/api.php` 内で作成された REST API は URL に `/api/` のプレフィックスが付与されます。

API の動作設定は、 `app/Providers/RouteServiceProvider.php` に記述されており、
これを修正することで、その挙動を変更することができます。

```php
    protected function mapApiRoutes()
    {
        Route::prefix('api') // URL に付与される プレフィックス
             ->middleware('api') // ミドルウェアのグループ
             ->namespace($this->namespace) // アクションクラスのデフォルト名前空間
             ->group(base_path('routes/api.php'));
    }
```

## ADR パターンの適用

一般的な Laravel での システム開発では MVC と呼ばれる設計の考え方が参照されますが、
API 開発においては ビュー部分のロジックは 「見た目」よりも API のレスポンス全体を意識したものが多くなってきます。

API の設計においては MVC パターンよりも ADR と呼ばれる、MVC 派生の設計モデルを利用する方がマッチするでしょう。

ADR は Action-Domain-Responder の略称で、 システムの構造を Action Domain Responder の３つに分割して捉えます。

### Domain

Domain は MVC でいうところの Model に相当する部分です。

ドメイン層の構造は、作ろうとするシステムによって様々ですが、
Repository パターンを適用したり Clean アーキテクチャを適用したり、設計は様々です。

シンプルなWebシステムでは、単純に Eloquent を利用する形のものでも十分でしょう。

### Responder

Responder は MVC でいうところの View に相当する部分です。

MVC の View と違って Responder は Response 全般を管理します。
すなわち、レスポンスボディだけでなく、ステータスコードやレスポンスヘッダの生成などを広く管理するクラス、ということになります。

Responder の責務は単純に response を生成するのみなのでシンプルな関数でも十分機能します。

Action と一対一で紐付けるように作成する場合は以下のような Responder が作成できるでしょう。

```php
<?php
namespace App\Http\Responder;

use Illuminate\Support\Collection;

/**
 * タスクの一覧を生成するレスポンス
 * at app/Http/Responder/TaskIndexResponder.php
 */
class TaskIndexResponder
{
    public function emit(Collection $tasks)
    {
        return response($tasks,200);
    }
}
```

小さな規模の開発では、すべての Action に対し一対一の Responder を作成するのが面倒な場合も多いかも知れません。

しかし、汎用的な形でも 最低限 Responder を用意して、
Response の生成を一つの場所にまとめておくのは、アプリケーションの保守上とても重要なことです。

### Action

Action は MVC でいうところの Controller に相当する部分です。

MVC の Controller と違って Action は アクションクラスと言う形で、１クラスで １つの API エンドポイントを担当します。

Controller が複数の URL を管理しながら Controller 内で 共通のロジックを取りまとめていた部分は Domain と呼ばれる Model 層に取りまとめられています。

Action は Domain 層からデータを受け取って最終的に Responder を呼出して レスポンスを生成します。

Action クラスは 以下のような形で実装します。

```php
<?php
namespace App\Http\Actions;

use App\Http\Responder\TaskIndexResponder;

/**
 * タスクの一覧を生成するレスポンス
 * at app/Http/Actions/TaskIndexAction.php
 */
class TaskIndexAction 
{
    public function handle(TaskIndexResponder $responder)
    {
        $tasks = \App\Task::all();
        return $responder->emit($tasks);
    }
}
```

実装された Action クラスをルートで利用する場合には、以下のような形になるでしょう。

```php
<?php
Route::get("tasks", \App\Http\Actions\TaskIndexAction::class."@handle");
```

アクションクラスで任意の名前空間を利用する場合、`app/Providers/RouteServiceProvider.php` にて
名前空間の設定を変更することを忘れないようにしてください。

```php
    protected function mapApiRoutes()
    {
        Route::prefix('api') 
             ->middleware('api') 
             // ->namespace($this->namespace) // これをコメントアウト
             ->group(base_path('routes/api.php'));
    }
```

## CORS の対応

Laravel で 作成した REST API を CORS 対応させる場合、laravel-cors のライブラリを利用するのが便利です。

https://github.com/barryvdh/laravel-cors

laravel-cors を利用するには、 composer でモジュールをインストールします。
Service Provider は Auto Discovery で自動認識されるため登録は不要です。 

```
$ composer require barryvdh/laravel-cors
```

API に CORS 対応を入れる場合、ミドルウェアとして `\Barryvdh\Cors\HandleCors::class` を登録します。





