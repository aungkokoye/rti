<?php
declare(strict_types=1);

namespace App\Modules\Task\Models;

use App\Models\User;
use App\Modules\Task\Database\Factories\TaskFactory;
use App\Modules\Task\Filters\TaskFilter;
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
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    public static array $status = ['pending', 'in_progress', 'completed'];

    public static array $priority = ['low', 'medium', 'high'];

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
        return (new TaskFilter())->search($query, $filters);
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
