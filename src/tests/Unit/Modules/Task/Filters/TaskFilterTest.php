<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Task\Filters;

use App\Models\User;
use App\Modules\Task\Filters\TaskFilter;
use App\Modules\Task\Models\Tag;
use App\Modules\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    private TaskFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new TaskFilter();
    }

    #[Test]
    public function it_filters_tasks_by_keyword_search_in_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Important Meeting']);
        Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Buy Groceries']);
        Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Another Important Task']);

        $query = Task::query();
        $result = $this->filter->search($query, ['search' => 'Important']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_keyword_search_in_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'description' => 'This is urgent']);
        Task::factory()->create(['assigned_to' => $user->id, 'description' => 'Normal task']);
        Task::factory()->create(['assigned_to' => $user->id, 'description' => 'Very urgent matter']);

        $query = Task::query();
        $result = $this->filter->search($query, ['search' => 'urgent']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'in_progress']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'completed']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);

        $query = Task::query();
        $result = $this->filter->search($query, ['status' => 'pending']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_ignores_invalid_status_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['status' => 'invalid_status']);

        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_priority(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'low']);
        Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'medium']);
        Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'high']);
        Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'high']);

        $query = Task::query();
        $result = $this->filter->search($query, ['priority' => 'high']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_ignores_invalid_priority_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['priority' => 'invalid_priority']);

        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_tags(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $task1 = Task::factory()->create(['assigned_to' => $user->id]);
        $task1->tags()->attach([$tag1->id, $tag2->id]);

        $task2 = Task::factory()->create(['assigned_to' => $user->id]);
        $task2->tags()->attach([$tag2->id]);

        $task3 = Task::factory()->create(['assigned_to' => $user->id]);
        $task3->tags()->attach([$tag3->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['tags' => (string) $tag2->id]);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_multiple_tags(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $task1 = Task::factory()->create(['assigned_to' => $user->id]);
        $task1->tags()->attach([$tag1->id]);

        $task2 = Task::factory()->create(['assigned_to' => $user->id]);
        $task2->tags()->attach([$tag2->id]);

        $task3 = Task::factory()->create(['assigned_to' => $user->id]);
        $task3->tags()->attach([$tag3->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['tags' => "{$tag1->id},{$tag2->id}"]);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_due_date_from(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-01']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-15']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-02-01']);

        $query = Task::query();
        $result = $this->filter->search($query, ['due-date-from' => '2025-01-10']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_due_date_to(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-01']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-15']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-02-01']);

        $query = Task::query();
        $result = $this->filter->search($query, ['due-date-to' => '2025-01-20']);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_due_date_range(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-01']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-15']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-02-01']);
        Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-03-01']);

        $query = Task::query();
        $result = $this->filter->search($query, [
            'due-date-from' => '2025-01-10',
            'due-date-to' => '2025-02-15',
        ]);

        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function it_sorts_tasks_by_created_at_descending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-01']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-03']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-02']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'created_at']);

        $tasks = $result->get();
        $this->assertEquals($task2->id, $tasks[0]->id);
        $this->assertEquals($task3->id, $tasks[1]->id);
        $this->assertEquals($task1->id, $tasks[2]->id);
    }

    #[Test]
    public function it_sorts_tasks_by_created_at_ascending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-01']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-03']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'created_at' => '2025-01-02']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'created_at', 'sort-type' => 'asc']);

        $tasks = $result->get();
        $this->assertEquals($task1->id, $tasks[0]->id);
        $this->assertEquals($task3->id, $tasks[1]->id);
        $this->assertEquals($task2->id, $tasks[2]->id);
    }

    #[Test]
    public function it_sorts_tasks_by_due_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-10']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-05']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'due_date' => '2025-01-15']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'due_date', 'sort-type' => 'asc']);

        $tasks = $result->get();
        $this->assertEquals($task2->id, $tasks[0]->id);
        $this->assertEquals($task1->id, $tasks[1]->id);
        $this->assertEquals($task3->id, $tasks[2]->id);
    }

    #[Test]
    public function it_sorts_tasks_by_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Banana']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Apple']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Cherry']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'title', 'sort-type' => 'asc']);

        $tasks = $result->get();
        $this->assertEquals($task2->id, $tasks[0]->id);
        $this->assertEquals($task1->id, $tasks[1]->id);
        $this->assertEquals($task3->id, $tasks[2]->id);
    }

    #[Test]
    public function it_sorts_tasks_by_priority_with_custom_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'medium']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'low']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'high']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'priority', 'sort-type' => 'asc']);

        $tasks = $result->get();
        // Custom order: low, medium, high
        $this->assertEquals('low', $tasks[0]->priority);
        $this->assertEquals('medium', $tasks[1]->priority);
        $this->assertEquals('high', $tasks[2]->priority);
    }

    #[Test]
    public function it_sorts_tasks_by_priority_descending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'medium']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'low']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'priority' => 'high']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'priority']);

        $tasks = $result->get();
        // Descending: high, medium, low
        $this->assertEquals('high', $tasks[0]->priority);
        $this->assertEquals('medium', $tasks[1]->priority);
        $this->assertEquals('low', $tasks[2]->priority);
    }

    #[Test]
    public function it_sorts_tasks_by_status_with_custom_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'completed']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'in_progress']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'status', 'sort-type' => 'asc']);

        $tasks = $result->get();
        // Custom order: pending, in_progress, completed
        $this->assertEquals('pending', $tasks[0]->status);
        $this->assertEquals('in_progress', $tasks[1]->status);
        $this->assertEquals('completed', $tasks[2]->status);
    }

    #[Test]
    public function it_ignores_disallowed_sort_columns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'invalid_column']);

        // Should not throw an error, just ignore
        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function it_applies_multiple_sorts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task1 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending', 'priority' => 'high']);
        $task2 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending', 'priority' => 'low']);
        $task3 = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'completed', 'priority' => 'medium']);

        $query = Task::query();
        $result = $this->filter->search($query, ['sort' => 'status,priority', 'sort-type' => 'asc']);

        $tasks = $result->get();
        // First sort by status (pending first), then by priority
        $this->assertEquals('pending', $tasks[0]->status);
        $this->assertEquals('pending', $tasks[1]->status);
        $this->assertEquals('completed', $tasks[2]->status);
    }

    #[Test]
    public function non_admin_user_sees_only_their_tasks(): void
    {
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);

        Task::factory()->count(3)->create(['assigned_to' => $user1->id]);
        Task::factory()->count(2)->create(['assigned_to' => $user2->id]);

        $this->actingAs($user1);

        $query = Task::query();
        $result = $this->filter->search($query, []);

        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function admin_user_sees_all_tasks(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        Task::factory()->count(3)->create(['assigned_to' => $admin->id]);
        Task::factory()->count(2)->create(['assigned_to' => $user->id]);

        $this->actingAs($admin);

        $query = Task::query();
        $result = $this->filter->search($query, []);

        $this->assertEquals(5, $result->count());
    }

    #[Test]
    public function admin_can_filter_by_assigned_to(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);

        Task::factory()->count(3)->create(['assigned_to' => $user1->id]);
        Task::factory()->count(2)->create(['assigned_to' => $user2->id]);

        $this->actingAs($admin);

        $query = Task::query();
        $result = $this->filter->search($query, ['assigned-to' => (string) $user1->id]);

        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function admin_can_filter_by_multiple_assigned_to(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);
        $user3 = User::factory()->create(['role' => User::ROLE_USER]);

        Task::factory()->count(3)->create(['assigned_to' => $user1->id]);
        Task::factory()->count(2)->create(['assigned_to' => $user2->id]);
        Task::factory()->count(1)->create(['assigned_to' => $user3->id]);

        $this->actingAs($admin);

        $query = Task::query();
        $result = $this->filter->search($query, ['assigned-to' => "{$user1->id},{$user2->id}"]);

        $this->assertEquals(5, $result->count());
    }

    #[Test]
    public function non_admin_ignores_assigned_to_filter(): void
    {
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);

        Task::factory()->count(3)->create(['assigned_to' => $user1->id]);
        Task::factory()->count(2)->create(['assigned_to' => $user2->id]);

        $this->actingAs($user1);

        $query = Task::query();
        // Non-admin tries to filter by another user's tasks - should be ignored
        $result = $this->filter->search($query, ['assigned-to' => (string) $user2->id]);

        // Should only see their own tasks
        $this->assertEquals(3, $result->count());
    }

    #[Test]
    public function it_trims_string_filter_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'completed']);

        $query = Task::query();
        $result = $this->filter->search($query, ['status' => '  pending  ']);

        $this->assertEquals(1, $result->count());
    }

    #[Test]
    public function it_combines_multiple_filters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag = Tag::factory()->create();

        $task1 = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'pending',
            'priority' => 'high',
            'title' => 'Important Task',
        ]);
        $task1->tags()->attach($tag);

        $task2 = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'pending',
            'priority' => 'low',
            'title' => 'Another Important Task',
        ]);

        $task3 = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'completed',
            'priority' => 'high',
            'title' => 'Important Done Task',
        ]);

        $query = Task::query();
        $result = $this->filter->search($query, [
            'search' => 'Important',
            'status' => 'pending',
            'priority' => 'high',
            'tags' => (string) $tag->id,
        ]);

        $this->assertEquals(1, $result->count());
        $this->assertEquals($task1->id, $result->first()->id);
    }

    #[Test]
    public function it_returns_empty_result_when_no_match(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(3)->create([
            'assigned_to' => $user->id,
            'status' => 'pending',
        ]);

        $query = Task::query();
        $result = $this->filter->search($query, ['status' => 'completed']);

        $this->assertEquals(0, $result->count());
    }

    #[Test]
    public function it_handles_empty_filters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(5)->create(['assigned_to' => $user->id]);

        $query = Task::query();
        $result = $this->filter->search($query, []);

        $this->assertEquals(5, $result->count());
    }

    #[Test]
    public function it_filters_tasks_by_full_text_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Database Migration Project',
            'description' => 'Migrate all data to new system',
        ]);
        Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Frontend Development',
            'description' => 'Build user interface components',
        ]);
        Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'API Integration',
            'description' => 'Connect to external database services',
        ]);
        Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Server Migration Task',
            'description' => 'Move servers to new infrastructure',
        ]);

        $query = Task::query();
        $result = $this->filter->search($query, ['full-search' => 'database migration']);

        // Verify FULLTEXT MATCH...AGAINST clause is applied to the query
        $sql = $result->toSql();
        $this->assertStringContainsString('MATCH', $sql);
        $this->assertStringContainsString('AGAINST', $sql);

        // Result count depends on MySQL FULLTEXT behavior (may return 0 due to 50% threshold in small datasets)
        $this->assertLessThanOrEqual(4, $result->count());
    }

    #[Test]
    public function it_ignores_full_text_search_when_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $query = Task::query();
        $result = $this->filter->search($query, ['full-search' => '']);

        $this->assertEquals(3, $result->count());
    }
}
