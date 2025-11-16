<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Models\User;
use App\Modules\Task\Models\Task;
use App\Traits\CustomPaginates;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomPaginatesTest extends TestCase
{
    use RefreshDatabase;

    private object $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new class {
            use CustomPaginates;
        };
    }

    #[Test]
    public function it_uses_default_per_page_when_not_specified(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(20)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET');
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
    }

    #[Test]
    public function it_uses_custom_default_per_page(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(30)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET');
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request, 25);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(25, $result->perPage());
    }

    #[Test]
    public function it_uses_per_page_from_request(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(20)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 10]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
    }

    #[Test]
    public function it_returns_length_aware_paginator_by_default(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET');
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
    }

    #[Test]
    public function it_returns_cursor_paginator_when_requested(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['pagination-type' => 'cursor']);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(CursorPaginator::class, $result);
    }

    #[Test]
    public function it_applies_cursor_pagination_with_custom_per_page(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(20)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', [
            'pagination-type' => 'cursor',
            'per-page' => 5,
        ]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(CursorPaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
        $this->assertCount(5, $result->items());
    }

    #[Test]
    public function it_casts_per_page_to_integer(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        // Pass per-page as string
        $request = Request::create('/tasks', 'GET', ['per-page' => '7']);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(7, $result->perPage());
        $this->assertIsInt($result->perPage());
    }

    #[Test]
    public function it_paginates_with_first_page(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(25)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 10]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
        $this->assertCount(10, $result->items());
    }

    #[Test]
    public function it_works_with_eloquent_builder(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 5]);
        $builder = Task::query()->where('assigned_to', $user->id);

        $result = $this->service->applyPagination($builder, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
        $this->assertCount(5, $result->items());
    }

    #[Test]
    public function it_works_with_has_many_relationship(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(8)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 3]);
        $hasManyRelation = $user->tasks();

        $result = $this->service->applyPagination($hasManyRelation, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(8, $result->total());
        $this->assertCount(3, $result->items());
    }

    #[Test]
    public function it_handles_empty_results(): void
    {
        $request = Request::create('/tasks', 'GET', ['per-page' => 10]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    #[Test]
    public function it_handles_per_page_greater_than_total_items(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(5)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 100]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
        $this->assertCount(5, $result->items());
        $this->assertEquals(1, $result->lastPage());
    }

    #[Test]
    public function it_ignores_other_pagination_type_values(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        // Use invalid pagination type
        $request = Request::create('/tasks', 'GET', ['pagination-type' => 'invalid']);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        // Should default to LengthAwarePaginator
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function cursor_pagination_has_next_cursor(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(15)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', [
            'pagination-type' => 'cursor',
            'per-page' => 5,
        ]);
        $query = Task::query();

        $result = $this->service->applyPagination($query, $request);

        $this->assertInstanceOf(CursorPaginator::class, $result);
        $this->assertTrue($result->hasMorePages());
        $this->assertNotNull($result->nextCursor());
    }
}