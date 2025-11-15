<?php
declare(strict_types=1);

namespace App\Modules\Task\Filters;

use App\Modules\Task\Models\Task;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
class TaskFilter
{
    private array $allowedSortColumns = ['created_at', 'due_date', 'priority', 'title', 'status'];

    public function search(EloquentBuilder| QueryBuilder $query, array $filters): EloquentBuilder | QueryBuilder
    {
        $filters = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filters);

        $this->applyKeyword($query, $filters);
        $this->applyFullText($query, $filters);
        $this->applyStatus($query, $filters);
        $this->applyPriority($query, $filters);
        $this->applyAssignedTo($query, $filters);
        $this->applyTags($query, $filters);
        $this->applyDueDate($query, $filters);
        $this->applySorting($query, $filters);

        return $query;
    }

    protected function applyKeyword(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        $query->where(function ($query) use ($filters) {
            // keyword search
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }
        });
    }

    protected function applyFullText(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['full-search'])) {
            $fullSearch = $filters['full-search'];
            $query->whereRaw(
                "MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$fullSearch]
            );
        }
    }

    protected function applyStatus(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['status']) && in_array($filters['status'], Task::$status, true)) {
            $query->where('status', $filters['status']);
        }
    }

    protected function applyPriority(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['priority']) && in_array($filters['priority'], Task::$priority, true)) {
            $query->where('priority', $filters['priority']);
        }
    }

    protected function applyAssignedTo(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (! auth()->user()->isAdmin()){
            $query->where('assigned_to', auth()->user()->id);
        } elseif (!empty($filters['assigned-to'])) {
            $assignedTo = array_map('trim', explode(',', $filters['assigned-to']));
            $query->whereIn('assigned_to', $assignedTo);
        }
    }

    protected function applyTags(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['tags'])) {
            $tagIds = array_map('trim', explode(',', $filters['tags']));

            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }
    }

    protected function applyDueDate(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['due-date-from'])) {
            $query->whereDate('due_date', '>=', $filters['due-date-from']);
        }

        if (!empty($filters['due-date-to'])) {
            $query->whereDate('due_date', '<=', $filters['due-date-to']);
        }
    }

    protected function applySorting(EloquentBuilder| QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['sort'])) {
            $sorts = explode(',', $filters['sort']);
            $sortType = (!empty($filters['sort-type']) && $filters['sort-type'] === 'asc') ? 'asc' : 'desc';

            foreach ($sorts as $sortColumn) {
                $sortColumn = trim($sortColumn);

                if (in_array($sortColumn, $this->allowedSortColumns, true)) {
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
    }
}
