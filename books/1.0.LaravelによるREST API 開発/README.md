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

## REST API の作成

まずは、 REST API で Hello World を作成してみましょう。

API に関する情報はすべて `routes/api.php` に記述します。

`routes/api.php` に以下のようなルートを記述することで REST API が生成されます。

```php
<?php
Route::get("/status",function(){
    return [
        "message" => "hello world"    
    ];
});
```


上記の様に記述した API は `GET /api/status` の形式でアクセスできます。

[Postman](https://www.getpostman.com/) などのツールを利用して実際にREST APIを実行してみましょう。

::: warning
`routes/web.php` に REST API を記述した場合、POST などの HTTP メソドを利用した際に
CSRF トークンが必要となり、 REST APIが非常に利用しづらくなります。
:::

## REST API の外部設計

REST API の構造は、リクエストとレスポンスに分けて考える事ができます。

### レスポンス

一般的に API のレスポンスは、JSON 形式で送信されます。

Laravel では ルート内で 配列をreturn することで、自動的にJSON形式のレスポンスを生成することができます。

```php
<?php
Route::get("status", function(){
    return [
      "status" => "OK",
      "message" => "no issues with systemn"
    ];
});
```

複数のキーや深い階層の配列を利用しても問題なくレスポンスを生成できます。

REST API のレスポンスは、メインの情報である ResponseBodyの他に、
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
    return response([
        "message" => "hello world"
    ],200,$headers);
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

```php
<?php
Route::post("status", function(){
    return [
      "status" => "OK",
      "message" => "no issues with systemn"
    ];
});
```

メソドが異なるAPI レスポンスを受けると Laravel は 405 のステータスコードを返します。

同一の URL でもメソド違いで異なる API を作成することが可能です。

```php
<?php
Route::get("status", function(){
    return [
      "status" => "OK",
      "message" => "this is get api"
    ];
});

Route::post("status", function(){
    return [
      "status" => "OK",
      "message" => "this is post api"
    ];
});
```

URLにパラメータを付与することができます。以下のように `{ }` を利用して記述した API は、
`/user/Tom` や `/user/John` などの形式でリクエストすることができます。

```php
<?php
Route::post("user/{name}", function($name){
    return [
      "user" => [
          "name" => $name
      ],
    ];
});
```

実際にURL で用いられた文字列は、 function の引数から `$name` で取得することが可能です。

`GET` 以外のメソドでは、リクエストボディとしてパラメータを付与する事も可能です。
リクエストボディの値は JSON 形式で送信され、 `request` 関数を用いてこれを取得することができます。

```php
<?php
Route::post("/status",function(){
    return [
        "message" => request()->get("message")    
    ];
});
```

以下のようなJSONを Request Body として送ることで、データを取得することが可能です。

```json
{
  "message" : "hello world"
}
```

単純に リクエストボディ全体を取得したい場合には、 `request()->all()` を取得します。

```php
<?php
Route::post("/status",function(){
    return [
        "message" => request()->all()    
    ];
});
```

また、API 通信では、認証やメタ情報を ヘッダを用いて送信することもあります。
ヘッダの値は `request()->header("hogehoge")` の形式で取得することも可能です。

## REST API と Guzzle の通信

Guzzle は PHP で REST API を叩くための ライブラリです。

composer を利用して以下のような形でインストールします。


```bash
$ composer require guzzlehttp/guzzle
```

今回は Connpass の API をインストールしてみましょう.
Connpass の API 仕様は以下の Webページで公開されています。

https://connpass.com/about/api/

Guzzle を使ってこの API を利用して、REST APIとして実装するためには以下のようなルートを記述します。

```php
<?php
use GuzzleHttp\Client;

Route::get("/connpass",function(){

    $client = new Client();
    $response = $client->request("GET","https://connpass.com/api/v1/event/");
    $responseBody = json_decode($response->getBody()->getContents(),true);

    return [
        "status" => "OK",
        "events" => $responseBody,
    ];
});
```

そのまま `$responseBody` を返すのではなく、必要なデータだけに絞ったり、
パラメータを利用して、結果を整形することも可能です。


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

## CORS の対応

Laravel で 作成した REST API を CORS 対応させる場合、laravel-cors のライブラリを利用するのが便利です。

https://github.com/barryvdh/laravel-cors

laravel-cors を利用するには、 composer でモジュールをインストールします。
Service Provider は Auto Discovery で自動認識されるため登録は不要です。 

```
$ composer require barryvdh/laravel-cors
```

API に CORS 対応を入れる場合、ミドルウェアとして `\Barryvdh\Cors\HandleCors::class` を登録します。

```php
<?php
Route::get('status', function () {
    //
})->middleware(\Barryvdh\Cors\HandleCors::class);
```


### CORS の設定

CORS の詳細な設定を行う場合、 設定ファイルを利用して管理を行います。

以下のコマンドを実行すると `config/cors.php` が生成され、 CORS の詳細な設定を行うことができるようになります。

```
$ php artisan vendor:publish --provider="Barryvdh\Cors\ServiceProvider"
```

生成される `config/cors.php` は以下のような形になります。

```
return [
     /*
     |--------------------------------------------------------------------------
     | Laravel CORS
     |--------------------------------------------------------------------------
     |
     | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
     | to accept any value.
     |
     */
    'supportsCredentials' => false,
    'allowedOrigins' => ['*'],
    'allowedHeaders' => ['Content-Type', 'X-Requested-With'],
    'allowedMethods' => ['*'], // ex: ['GET', 'POST', 'PUT',  'DELETE']
    'exposedHeaders' => [],
    'maxAge' => 0,
];
```



