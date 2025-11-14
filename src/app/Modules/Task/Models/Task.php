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
use Illuminate\Database\Query\Builder as QueryBuilder;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

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
//        return
//            $query->when($filters['search'] ?? null, function ($query, $search) {
//                // where and or-where are grouped here to avoid logic issues
//                $query->where(function ($query) use ($search) {
//                    $query->where('title', 'like', "%{$search}%")
//                        ->orWhere('description', 'like', "%{$search}%")
//                        ->orWhereHas('employer', function ($query) use ($search) {
//                            $query->where('company_name', 'like', "%{$search}%");
//                        });
//                });
//            })->when($filters['min-salary'] ?? null, function ($query, $salary) {
//                $query->where('salary', '>=', $salary);
//            })
//                ->when($filters['mix-salary'] ?? null, function ($query, $salary) {
//                    $query->where('salary', '<=', $salary);
//                })
//                ->when($filters['experience'] ?? null, function ($query, $experience) {
//                    $query->where('experience', $experience);
//                })
//                ->when($filters['category'] ?? null, function ($query, $category) {
//                    $query->where('category', $category);
//                });

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
