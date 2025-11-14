<?php
declare(strict_types=1);

namespace App\Traits;


use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

trait CustomPaginates
{
    public function applyPagination(
        Model|QueryBuilder|EloquentBuilder| HasMany $builder,
        Request $request,
        int $defaultPerPage = 15 // default items per page
    ): LengthAwarePaginator | CursorPaginator
    {
        $perPage = $request->get('per-page', $defaultPerPage);

        if ($request->get('pagination-type') === 'cursor') {
            return $builder->cursorPaginate((int)$perPage);
        }

        return $builder->paginate((int)$perPage);
    }
}
