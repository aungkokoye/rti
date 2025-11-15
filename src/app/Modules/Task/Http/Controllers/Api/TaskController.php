<?php
declare(strict_types=1);

namespace App\Modules\Task\Http\Controllers\Api;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Requests\CreateTaskRequest;
use App\Modules\Task\Requests\UpdateTaskRequest;
use App\Modules\Task\Resources\TaskResource;
use App\Modules\Task\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class TaskController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     * URL example: api/tasks?search=&full-search=&status=&priority=&assigned-to=&tags=&due-date-from=&due-date-to=
     * &sort=title&sort-type=desc&per-page=20&page=2&pagination-type=cursor
     */
    public function index(Request $request, TaskService $taskService): AnonymousResourceCollection
    {
        return TaskResource::collection($taskService->searchTasks($request));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTaskRequest $request, TaskService $taskService): TaskResource
    {
        $validated = $request->validated();
        $task = $taskService->createTask($validated);

        return new TaskResource($taskService->attachRelations($task));
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task, TaskService $taskService): TaskResource
    {
        return new TaskResource($taskService->attachRelations($task));
    }

    /**
     * Update the specified resource in storage.
     * Prevent race condition with optimistic locking
     */
    public function update(UpdateTaskRequest $request, Task $task, TaskService $taskService): TaskResource
    {
        $validated = $request->validated();
        $updatedTask = $taskService->updateTask($task, $validated);

        return new TaskResource($taskService->attachRelations($updatedTask));
    }

    /**
     * Restore the soft-deleted resource in storage.
     */
    public function restore(int $id, TaskService $taskService): TaskResource
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        return new TaskResource($taskService->attachRelations($task));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): Response
    {
        $task->delete();

        return response()->noContent();
    }

    public function toggleStatus(Task $task, TaskService $taskService): TaskResource
    {
        $taskService->toggleTaskStatus($task);

        return new TaskResource($taskService->attachRelations($task));
    }
}
