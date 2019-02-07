---
title: Laravel における認証
---

# Laravel における認証

REST API における認証は、ページアプリケーションにおける
セッションの認証と異なり、トークンを用いた認証が利用されます。

## 認証処理の準備

ログイン処理を実装する上で、以下の ３種類の API を実装する必要があります。

- ログイン用 API : ユーザ認証情報を利用して、ユーザトークンを発行する
- ログアウト用 API : 発行されたユーザトークンを無効化する
- 認証付き API : ユーザトークンを所有しているユーザに対してのみ有効なAPI 

### 準備

認証に必要なテーブルを作成します。
デフォルトで用意されているユーザテーブルに、トークン情報を保存する
`token`列を追加しましょう。

```bash
$ php artisan make:migration auth_users
```

マイグレーションファイルは、以下のような形になります。

```php
<?php
class AuthUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('token')->unique()->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
}
```

テスト用ユーザ情報を作成する シーダーも作成しておきましょう。

```bash
$ php artisan make:seeder UsersSeeder
```

作成された、`database/seeds/UsersSeeder.php` を以下のような形で作成します。

factory を利用して 5 件のテストデータを作成しています。

```php
<?php

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    use \Illuminate\Foundation\Testing\WithFaker;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = factory(App\User::class, 5)->create();
    }
}
```

最後にマイグレーションとシーダーの実行を行います。

```bash
$ php artisan migrate
$ php artisan db:seed --class UsersSeeder
```

データベースに ５件のテストデータ投入が確認できたら準備は完了です。

::: tip
Factory を利用したテストデータ生成の詳細については [こちら](/9.2%20Factory%20%E3%82%92%E4%BD%BF%E3%81%A3%E3%81%9F%E3%83%86%E3%82%B9%E3%83%88%E3%83%87%E3%83%BC%E3%82%BF%E3%81%AE%E7%94%9F%E6%88%90/) をご確認ください。
:::

## REST API におけるトークン認証

今回は以下の ３つの REST API を作成します。

- ログイン用 API : ユーザ認証情報を利用して、ユーザトークンを発行する
- ログアウト用 API : 発行されたユーザトークンを無効化する
- 認証付き API : ユーザトークンを所有しているユーザに対してのみ有効なAPI 

### ログイン用 API の作成

ログイン用の API を `POST /auth/login` の形式で作成します。

認証情報は 以下の形式で送信する想定です。

```json
{
    "email" : "sample_user@sample.com",
    "password" : "password"
}
```

`route/api.php` には以下の形式で REST API を定義します。

```php
<?php
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
        abort(401);
    }
});
```

`/api/auth/login` に `POST` 形式で API を発行すれば、動作の確認が可能です。

認証エラーの場合、 401 が返却されます。正しい認証情報を送信した場合、ユーザ情報が API 経由で返却されるはずです。
DB の内部を確認して 有効な Email と パスワード(factory 経由で格納したデータの場合 `secret`) を利用してリクエストを送ってみましょう。

上記のコードでは `request` 関数を利用して送信された 
Email と Password を取得しています。

```php
$email = request()->get("email");
$password = request()->get("password");
```

取得した Email を利用して、ユーザを検索します。

ユーザが取得できて パスワードの確認が取れた場合のみ、正常系ルートに移ります。

パスワードのチェックには Hash クラスを利用します。

ユーザが見つからなかった場合には `abort(401)` を実行することで 401 のステータスコードを返却する事ができます。

```php
$user = \App\User::where("email",$email)->first();
if ($user && Hash::check($password, $user->password)) {        
    ...
}else{
    abort(401);
}

```

if 文の内部では ログインに成功した後のフローを処理しています。

`str_random` を利用してランダムな文字列で トークンを生成します。
生成したトークンを Eloquent に渡して 保存し、Response を生成します。

```php
$token = str_random();
$user->token = $token;
$user->save();
return [
    "token" => $token,
    "user" => $user
];
```

### 認証付き API の作成

次に 認証付き API を作成します。
ここでは、ログインユーザのプロフィール情報を返す API を、`GET /profile` の形式で作成します。

認証情報は リクエストヘッダを利用して以下の形式で送信する想定です。

```text
Authorization: Bearer {YOUR_ACCESS_TOKEN}
```

上記の形式は Bearer 方式と呼ばれる一般的な認証トークンの送信形式です。


```php
<?php
Route::get("/profile",function(){
    $token = request()->bearerToken();
    $user = \App\User::where("token",$token)->first();
    if ($token && $user) {        
        return [
            "user" => $user
        ];
    }else{
        abort(401);
    }
});
```

`/api/profile` に `GET` 形式で API を発行すれば、動作の確認が可能です。

うまく動作すれば、トークンに紐付いたユーザのプロフィール情報が確認できるはずです。

上記コードでは、ヘッダに渡された トークン情報を取得するために、 `request()->bearerToken()` を利用しています。

取得したトークンを基にユーザを検索して、該当するユーザがいない(トークンが不正) な場合、 401 のレスポンスを返却しています。

```php
$token = request()->bearerToken();
$user = \App\User::where("token",$token)->first();
if ($token && $user) {        
    // ...
}else{
    abort(401);
}
```

if 文の内部では取得したユーザ情報を基に、プロフィール用のレスポンスを生成しています。

### ログアウト API の作成

最後に ログアウト用の API を作成しましょう。
`POST /auth/logout` の形式でトークンを無効化する処理を記述します。

認証情報(トークン)は 認証付きAPI と同じく、リクエストヘッダ経由で送信する想定です。

```php
<?php
Route::post("/auth/logout",function(){
    $token = request()->bearerToken();
    $user = \App\User::where("token",$token)->first();
    if ($token && $user) {
        $user->token = null;
        $user->save();
        return [];
    }else{
        abort(401);
    }
});
```

認証付きルートと同じく、トークンの有効性を判定するためにヘッダから渡されたトークンを利用して
ユーザの検索を行っています。

ユーザがヒットした場合、該当するユーザのトークン列を空にしてトークンを無効化することで、ログアウトの処理が完了します。

## Laravel Auth の利用

先程記述したコードでは、認証付きルートで毎回 トークンの有効性チェックを行う必要があり、
処理の記述が煩雑という欠点があります。

認証機構に関しては Laravel で Auth という機能が提供されており、
これを利用するほうが便利なケースも多いでしょう。

### Request Guard の記述

REST APIのようなセッションを利用しないシンプルな認証処理においては、
Request Guard の機能を用いた認証設定がシンプルで簡単です。

認証の設定は `app/Providers/AuthServiceProvider.php` を利用するのが良いでしょう。

```php
use Illuminate\Support\Facades\Auth;

//...

public function boot()
{
    $this->registerPolicies();

    Auth::viaRequest('custom-token', function ($request) {
        $token = request()->bearerToken();
        if($token){
            return \App\User::where("token",$token)->first();        
        }else{
            return null;
        }
    });
}
```

`AuthServiceProvider` の boot セクションに 
`Auth::viaRequest` を記述することで認証の設定を行うことができます。

登録した `custom-token` という名前の認証ドライバを 利用可能にするために、
`config/auth.php` にて Guards として登録します。

例えば 認証ドライバ `custom-token` を `api` という名前で 利用可能にするためには、
次のような設定になります。

```php
'guards' => [
    'api' => [
        'driver' => 'custom-token',
    ],
],
```

上記のような設定を行った場合、認証ユーザの取得は `Auth` クラス経由で行えるようになります。

```php
<?php
Route::get("/profile",function(){
    $user = Auth::guard("api")->user();
    if ($user) {        
        return [
            "user" => $user
        ];
    }else{
        abort(401);
    }
});
```

`config/auth.php` にて デフォルトの認証を `api` にしている場合、
`Auth::guard("api")->user()` は単に `Auth::user()` と記述できます。

```php
'defaults' => [
    'guard' => 'api',
],
```

### ミドルウェアによる ルートガード

Auth の設定ができたら ミドルウェアを用いて認証付きルートを保護することが可能です。

```php
<?php
Route::get("/profile",function(){
    $user = Auth::guard("api")->user();
    return [
        "user" => $user
    ];
})->middleware("auth:api");
```

このケースでも、`config/auth.php` にて デフォルトの認証を `api` にしている場合、
`middleware("auth")` の形式で ミドルウェアを指定できます。

ミドルウェアでルートガードの設定を行った場合、
ルート内の処理は認証済みであることが前提となるため、
上記のように　if 文による分岐をなくすことができます。

リクエスト時に、 `X-Requested-With: XMLHttpRequest` のヘッダを付与することで、
非認証時に 正しく 401 のレスポンスが返されます。

## エラーハンドリング

認証エラー時のレスポンスについても考えてみましょう。

Laravel では API からのリクエスト送信時に、自動的に JSON 形式でエラーが表示されるよう、
実装がなされています。

「API からのリクエスト」かどうかについては、 
リクエストヘッダ上の`X-Requested-With: XMLHttpRequest`の判定がなされます。

例えば認証エラーの際には、 401 のステータスコードで次のような レスポンスが返却されます。

```json
{
    "message": "Unauthenticated."
}
```

このレスポンスを修正する場合には、  `app/Exceptions/Handler.php` の render メソドを次のように修正します。

```php
<?php
namespace App\Exceptions;

//...

use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    public function render($request, Exception $exception)
    {
        if($exception instanceof AuthenticationException){
            return response([
                "message" => "認証エラーです"
            ],401);
        }
        return parent::render($request, $exception);
    }
}
```

`Handler::render` はアプリケーション内で生成されたエラーに対して、
レスポンスの生成を担当する箇所です。

ミドルウェアによるHTTPエラー時には `Illuminate\Auth\AuthenticationException`
が投げられるためこれを補足してやれば、エラーを処理をすることができます。

`abort(404)` の際には `\Symfony\Component\HttpKernel\Exception\NotFoundHttpException` が
それ以外の `abort` では、 `\Symfony\Component\HttpKernel\Exception\HttpException` が投げられるため、
これらも同様のかたちで処理することができます。

## Try more !

### Multi Auth の設定

認証用の処理は、複数定義することで、
例えば ユーザ用の認証とスタッフ用の認証とを区分けすることができます。

複数の種別の認証を処理できるよう、スタッフ用のテーブル `staffs` を作成し、
ユーザ用の認証ルート、スタッフ用の認証ルートを個別に作成してみましょう。

### ユーザ登録APIの設定

ユーザ登録用の API を作成して、 パスワードのハッシュ化と照合のフローを確認しておきましょう。
 
ハッシュデータを作成するには、 Hash クラスを使って `Hash::make($password)` のようにします。

### ユーザIDによるヘッダ認証の追加

複数タブで認証付きアプリケーションを利用する際に、
タブをまたいでログインを変更していた際に、意図しないユーザ情報でAPIを送信してしまうケースがあります。

一部のアプリケーションでは、これを防ぐために、ヘッダに ユーザIDやメールアドレスを追加で送信して、
ご操作を防ぐフローが取られています。

独自のリクエストヘッダ `X-USER-ID` などを利用して token + α の認証を追加できるように修正してみましょう。
