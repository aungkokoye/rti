<?php
declare(strict_types=1);

namespace App\Modules\Task\Http\Controllers\Api;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Resources\TaskResource;
use App\Traits\CanLoadRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller as BaseController;

class TaskController extends BaseController
{
    use CanLoadRelations;

    private array $relations = ['user', 'tags'];

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $builder = $this->loadRelations(Task::query());

        $tasks = $builder->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Restore the soft-deleted resource in storage.
     */
    public function restore(Task $task)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        //
    }
}
