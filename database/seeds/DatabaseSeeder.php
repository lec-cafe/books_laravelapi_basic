<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Task::create(["name" => "牛乳を買う"]);
        \App\Task::create(["name" => "本を読む"]);
        \App\Task::create(["name" => "部屋を掃除する"]);
        // $this->call(UsersTableSeeder::class);
    }
}
