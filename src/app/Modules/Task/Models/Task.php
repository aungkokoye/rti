<?php
declare(strict_types=1);

namespace App\Modules\Task\Models;

use App\Models\User;
use App\Modules\Task\Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 *
 * @method static filter(array $filters)
 * @method static create(array $array)
 * @property string $status
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    public static array $status = ['pending', 'in_progress', 'completed'];

    public static array $priority = ['low', 'medium', 'high'];

    public static array $allowedSortColumns = ['created_at', 'due_date', 'priority', 'title', 'status'];
    protected $fillable = ['title', 'description', 'status', 'priority', 'version', 'metadata', 'due_date', 'assigned_to'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag')->withTimestamps();
    }

    /**
     * @param $filters array<integer,string>
     * @method static filter(array $filters)
     */
    public function scopeFilter(EloquentBuilder| QueryBuilder $query, array $filters): EloquentBuilder | QueryBuilder
    {
        // Trim all filter values
        $filters = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filters);

        // Grouped OR conditions
        $query->where(function ($query) use ($filters) {
                // keyword search
                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });

        // full-text search
        if (!empty($filters['full-search'])) {
            $fullSearch = $filters['full-search'];
            $query->whereRaw("MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)", [$fullSearch]);
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::$status, true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority']) && in_array($filters['priority'], self::$priority, true)) {
            $query->where('priority', $filters['priority']);
        }

        // if user is not admin, only show their tasks
        if (! auth()->user()->isAdmin()){
            $query->where('assigned_to', auth()->user()->id);
        } elseif (!empty($filters['assigned-to'])) {
            $assignedTo = array_map('trim', explode(',', $filters['assigned-to']));
            $query->whereIn('assigned_to', $assignedTo);
        }


        if (!empty($filters['tags'])) {
            $tagIds = array_map('trim', explode(',', $filters['tags']));

            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        if (!empty($filters['due-date-from'])) {
            $query->whereDate('due_date', '>=', $filters['due-date-from']);
        }

        if (!empty($filters['due-date-to'])) {
            $query->whereDate('due_date', '<=', $filters['due-date-to']);
        }

        if (!empty($filters['sort'])) {
            $sorts = explode(',', $filters['sort']);
            $sortType = (!empty($filters['sort-type']) && $filters['sort-type'] === 'asc') ? 'asc' : 'desc';

            foreach ($sorts as $sortColumn) {
                $sortColumn = trim($sortColumn);

                if (in_array($sortColumn, self::$allowedSortColumns, true)) {
                    // Handle priority sorting with custom order (low, medium, high)
                    if ($sortColumn === 'priority') {
                        $query->orderByRaw("FIELD(priority, 'low', 'medium', 'high') " . $sortType);
                    }
                    // Handle status sorting with custom order (pending, in_progress, completed)
                    elseif ($sortColumn === 'status') {
                        $query->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed') " . $sortType);
                    }
                    else {
                        $query->orderBy($sortColumn, $sortType);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }


    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
