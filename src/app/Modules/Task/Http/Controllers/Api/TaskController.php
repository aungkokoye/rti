<?php
declare(strict_types=1);

namespace App\Modules\Task\Http\Controllers\Api;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Requests\CreateTaskRequest;
use App\Modules\Task\Requests\UpdateTaskRequest;
use App\Modules\Task\Resources\TaskResource;
use App\Traits\CanLoadRelations;
use App\Traits\CustomPaginates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskController extends BaseController
{
    use CanLoadRelations, CustomPaginates;

    private array $relations = ['user', 'tags'];

    private array $allowedFilters = [
        'search',
        'full-search',
        'status',
        'priority',
        'assigned-to',
        'tags',
        'due-date-from',
        'due-date-to',
        'sort',
        'sort-type',
    ];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['index']);
    }

    /**
     * Display a listing of the resource.
     * URL example: api/tasks?search=&full-search=&status=&priority=&assigned-to=&tags=&due-date-from=&due-date-to=
     * &sort=title&sort-type=desc&per-page=20&page=2&pagination-type=cursor
     */
    public function index(Request $request): AnonymousResourceCollection
    {
//        $builder = $this->loadRelations( // for eager loading
//            Task::filter($request->only($this->allowedFilters)) // for filters
//        );

        // App\Modules\Task\Models\Task::scopeFilter => filter()
        $builder = Task::with(['user', 'tags'])
            ->withTrashed()
            ->filter($request->only($this->allowedFilters));

        $tasks = $this->applyPagination($builder, $request);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTaskRequest $request): TaskResource
    {
        $validated = $request->validated();
        $task = Task::create($validated);

        if (!empty($validated['tags'])) {
            $task->tags()->attach($validated['tags']);
        }

        return new TaskResource($this->loadRelations($task));
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($this->loadRelations($task));
    }

    /**
     * Update the specified resource in storage.
     * Prevent race condition with optimistic locking
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;

        // Optimistic locking: atomic version check + update
        $affected = DB::table('tasks')
            ->where('id', $task->id)
            ->where('version', $task->version)
            ->update(
                Arr::except($validated, ['tags']) + ['version' => DB::raw('version + 1')]
            );

        if (!$affected) {
            abort(409, 'Task modified by another user');
        }

        $task->refresh(); // upload the latest data

        if (!empty($tags)) {
            $task->tags()->sync($validated['tags']);
        }

        return new TaskResource($this->loadRelations($task));
    }

    /**
     * Restore the soft-deleted resource in storage.
     */
    public function restore(int $id): TaskResource
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        return new TaskResource($this->loadRelations($task));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): Response
    {
        $task->delete();

        return response()->noContent();
    }

    public function toggleStatus(Task $task): TaskResource
    {
        $currentIndex = array_search($task->status, Task::$status);
        $nextIndex = $currentIndex + 1 !== count(Task::$status) ? $currentIndex + 1 : 0;
        $task->update(['status' => Task::$status[$nextIndex]]);

        return new TaskResource($this->loadRelations($task));
    }
}
