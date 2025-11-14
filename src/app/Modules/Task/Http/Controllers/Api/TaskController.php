<?php
declare(strict_types=1);

namespace App\Modules\Task\Http\Controllers\Api;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Resources\TaskResource;
use App\Traits\CanLoadRelations;
use App\Traits\CustomPaginates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller as BaseController;

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
        $builder = Task::with(['user', 'tags'])->filter($request->only($this->allowedFilters));

        $tasks = $this->applyPagination($builder, $request);

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
