<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

trait CanLoadRelations
{
    /**
     * Add relation/s to query or model
     *
     * query param: include=relation1,relation2
     */
    public function loadRelations(
        Model|QueryBuilder|EloquentBuilder| HasMany  $for,
        ?array $relations = null
    ): Model|QueryBuilder|EloquentBuilder|HasMany
    {
        $allowedRelations = $relations ?? $this->relations ?? [];
        foreach($allowedRelations as $relation) {
            $for->when(
                $this->shouldIncludeRelations($relation),
                fn($q) => $for instanceof Model ? $for->load($relation) : $q->with($relation)
            );
        }

        return $for;
    }

    /**
     * Check requested relation/s can load or not
     */
    private function shouldIncludeRelations(string $relation): bool
    {
        $include = request()->query('include');

        if (empty($include)) {
            return false;
        }

        $requestRelations = array_map('trim', explode(',', $include));

        return in_array($relation, $requestRelations);
    }
}
