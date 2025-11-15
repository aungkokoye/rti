<?php
declare(strict_types=1);

namespace App\Modules\Task\Services;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Notifications\TaskActionNotification;
use App\Traits\CanLoadRelations;
use App\Traits\CustomPaginates;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskService
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

    public function updateTask(Task $task, array $data): Task
    {
        $tags = $data['tags'] ?? null;

        // Optimistic locking: atomic update
        $affected = DB::table('tasks')
            ->where('id', $task->id)
            ->where('version', $task->version)
            ->update(
                Arr::except($data, ['tags']) + ['version' => DB::raw('version + 1')]
            );

        if (!$affected) {
            abort(409, 'Task modified by another user');
        }

        // Reload updated model
        $task->refresh();

        // Handle tags
        if (!empty($tags)) {
            $task->tags()->sync($tags);
        }

        return $task;
    }

    public function createTask(array $data): Task
    {
        $task = Task::create($data);

        if (!empty($data['tags'])) {
            $task->tags()->attach($data['tags']);
        }

        return $task;
    }

    public function toggleTaskStatus(Task $task): void
    {
        $currentIndex = array_search($task->status, Task::$status);
        $nextIndex = $currentIndex + 1 !== count(Task::$status) ? $currentIndex + 1 : 0;
        $task->update(['status' => Task::$status[$nextIndex]]);
    }

    /**
     * Search tasks with filters and pagination
     * Includes soft-deleted tasks <withTrashed()>
     */
    public function searchTasks(Request $request): LengthAwarePaginator | CursorPaginator
    {
        // for eager loading
//        $builder = $this->loadRelations(
//            // for filters
//            Task::filter($request->only($this->allowedFilters))
//        );

        // App\Modules\Task\Models\Task::scopeFilter => filter()
        $builder = Task::with(['user', 'tags'])
            ->withTrashed()
            ->filter($request->only($this->allowedFilters));

        return $this->applyPagination($builder, $request);
    }

    public function attachRelations(Task $task): Model|QueryBuilder|EloquentBuilder|HasMany
    {
        return $this->loadRelations($task);
    }

    public function statusChangeNotification(Task $task, string $action): void
    {
        if (auth()->user()->id !== $task->assigned_to) {
            $task->user->notify(new TaskActionNotification($task, $action));
        }
    }
}
