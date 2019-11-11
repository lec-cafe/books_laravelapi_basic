## 構成

### 1. Laravel で タスクリスト APIを作る

- 作成するAPI 概要
- データベースのセットアップ
- タスク追加 API の作成
- タスク一覧取得 API の作成 
- タスク更新 API の作成
- タスク削除 API の作成

### 2. REST API の設計

- ルートの設計
- ヘッダの設計
- レスポンスの設計

::: tip
`routes/api.php` に記述した API 定義のURLには自動的に `/api` のプレフィックスが付与されます。
この挙動は、後述する REST APIの設定 にて変更する事が可能です。 
:::


タスクを更新する場合には PATCH メソドを利用して、以下のように記述します。

```php
<?php
Route::patch("/task/{id}",function($id){
    $task = \App\Task::find($id);
    if($task){
        $task->name = request()->get("name");
        $task->save();
    }    
    return [];
});
```




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


### 3. Eloquent を用いたデータベース操作

- テーブルの作成
- Eloquent の作成
- レコードの取得
- レコードの検索
- レコードの作成
- レコードの更新
- レコードの削除

他にもデータベースからデータを検索する際には、 where メソドを利用する事ができます。

検索結果からデータをひとつだけ取得する場合は `first()` でデータを取得します。

```php
<?php
Route::get("/task/{id}",function($id){
    return [
        "status" => "OK",
        "task" => \App\Task::where("id",$id)->first(),
    ];
});
```

検索結果からデータを複数取得する場合は `get()` でデータを取得します。

```php
<?php
Route::get("/tasks/",function(){
    return [
        "status" => "OK",
        "tasks" => \App\Task::where("id",">",3)->get(),
    ];
});
```

### 4. PHPUnit を用いた ユニットテスト

- ユニットテストの作成

### 5. Telescope を用いたデバッグ

- telescope のインストール
- 

### 9

- 環境構築(Vagrant)
- 環境構築(Mac)
- バリデーション
