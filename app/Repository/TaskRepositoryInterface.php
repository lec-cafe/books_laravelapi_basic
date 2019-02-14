<?php
namespace App\Repository;

interface TaskRepositoryInterface
{
    public function getList($sort,$limit);
}
