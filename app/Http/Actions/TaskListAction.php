<?php
namespace App\Http\Actions;

use GuzzleHttp\Promise\TaskQueueInterface;

class TaskListAction
{
    protected $repo;

    public function __construct(TaskQueueInterface $repo)
    {
        $this->repo = $repo;
    }


    public function handle()
    {
        $sort = request()->get("sort","DESC");
        $limit = request()->get("limit", 10);

        return [
            "tasks" => $this->repo->getList($sort,$limit)
        ];
    }
}
