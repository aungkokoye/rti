<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Task\Services;

use App\Jobs\AuditLogJob;
use App\Models\User;
use App\Modules\Task\Models\Tag;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Notifications\TaskActionNotification;
use App\Modules\Task\Services\TaskService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_task_without_tags(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'due_date' => now()->addDays(7),
        ];

        $task = $this->taskService->createTask($data);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test Description', $task->description);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals($user->id, $task->assigned_to);
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
    }

    #[Test]
    public function it_creates_task_with_tags(): void
    {
        $user = User::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $data = [
            'title' => 'Task with Tags',
            'description' => 'Description',
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $user->id,
            'tags' => $tags->pluck('id')->toArray(),
        ];

        $task = $this->taskService->createTask($data);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertCount(3, $task->tags);
        foreach ($tags as $tag) {
            $this->assertTrue($task->tags->contains($tag));
        }
    }

    #[Test]
    public function it_updates_task_successfully(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Original Title',
            'version' => 1,
        ]);

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ];

        $updatedTask = $this->taskService->updateTask($task, $data);

        $this->assertEquals('Updated Title', $updatedTask->title);
        $this->assertEquals('Updated Description', $updatedTask->description);
        $this->assertEquals(2, $updatedTask->version);
    }

    #[Test]
    public function it_updates_task_with_tags(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'version' => 1,
        ]);
        $newTags = Tag::factory()->count(2)->create();

        $data = [
            'title' => 'Updated with Tags',
            'tags' => $newTags->pluck('id')->toArray(),
        ];

        $updatedTask = $this->taskService->updateTask($task, $data);

        $this->assertCount(2, $updatedTask->tags);
        foreach ($newTags as $tag) {
            $this->assertTrue($updatedTask->tags->contains($tag));
        }
    }

    #[Test]
    public function it_fails_update_with_version_conflict(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'version' => 1,
        ]);

        // Simulate another user updating the task
        DB::table('tasks')
            ->where('id', $task->id)
            ->update(['version' => 2]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->taskService->updateTask($task, ['title' => 'Conflicting Update']);
    }

    #[Test]
    public function it_toggles_status_from_pending_to_in_progress(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'pending',
        ]);

        $this->taskService->toggleTaskStatus($task);

        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    #[Test]
    public function it_toggles_status_from_in_progress_to_completed(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'in_progress',
        ]);

        $this->taskService->toggleTaskStatus($task);

        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    #[Test]
    public function it_toggles_status_from_completed_to_pending(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'completed',
        ]);

        $this->taskService->toggleTaskStatus($task);

        $task->refresh();
        $this->assertEquals('pending', $task->status);
    }

    #[Test]
    public function it_searches_tasks_with_default_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Task::factory()->count(20)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET');

        $result = $this->taskService->searchTasks($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    #[Test]
    public function it_searches_tasks_with_custom_per_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['per-page' => 5]);

        $result = $this->taskService->searchTasks($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
    }

    #[Test]
    public function it_searches_tasks_with_cursor_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Task::factory()->count(10)->create(['assigned_to' => $user->id]);

        $request = Request::create('/tasks', 'GET', ['pagination-type' => 'cursor']);

        $result = $this->taskService->searchTasks($request);

        $this->assertInstanceOf(CursorPaginator::class, $result);
    }

    #[Test]
    public function it_includes_soft_deleted_tasks_in_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $deletedTask = Task::factory()->create(['assigned_to' => $user->id]);
        $deletedTask->delete();

        $request = Request::create('/tasks', 'GET');

        $result = $this->taskService->searchTasks($request);

        $this->assertEquals(2, $result->total());
    }

    #[Test]
    public function it_eager_loads_relations_in_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $tags = Tag::factory()->count(2)->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $task->tags()->attach($tags);

        $request = Request::create('/tasks', 'GET');

        $result = $this->taskService->searchTasks($request);
        $firstTask = $result->first();

        $this->assertTrue($firstTask->relationLoaded('user'));
        $this->assertTrue($firstTask->relationLoaded('tags'));
    }

    #[Test]
    public function it_attaches_relations_to_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Mock request with include parameter
        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,tags']));

        $result = $this->taskService->attachRelations($task);

        $this->assertInstanceOf(Task::class, $result);
    }

    #[Test]
    public function it_sends_notification_when_user_is_different(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $assignedUser = User::factory()->create();

        $this->actingAs($admin);

        $task = Task::factory()->create(['assigned_to' => $assignedUser->id]);
        $task->setRelation('user', $assignedUser);

        $this->taskService->statusChangeNotification($task, 'deleted');

        Notification::assertSentTo(
            $assignedUser,
            TaskActionNotification::class,
            function ($notification, $channels) use ($task) {
                return true;
            }
        );
    }

    #[Test]
    public function it_does_not_send_notification_to_self(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $task->setRelation('user', $user);

        $this->taskService->statusChangeNotification($task, 'completed');

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_dispatches_audit_log_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->taskService->saveToAuditLog($task, 'created');

        Queue::assertPushed(AuditLogJob::class, function ($job) {
            return true;
        });
    }

    #[Test]
    public function it_loads_relations_before_saving_to_audit_log(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $tags = Tag::factory()->count(2)->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $task->tags()->attach($tags);

        // Ensure relations are not loaded initially
        $this->assertFalse($task->relationLoaded('user'));
        $this->assertFalse($task->relationLoaded('tags'));

        $this->taskService->saveToAuditLog($task, 'updated');

        // Relations should be loaded after the call
        $this->assertTrue($task->relationLoaded('user'));
        $this->assertTrue($task->relationLoaded('tags'));

        Queue::assertPushed(AuditLogJob::class);
    }

    #[Test]
    public function it_creates_task_with_empty_tags_array(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'Task without Tags',
            'description' => 'Description',
            'status' => 'pending',
            'priority' => 'low',
            'assigned_to' => $user->id,
            'tags' => [],
        ];

        $task = $this->taskService->createTask($data);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertCount(0, $task->tags);
    }

    #[Test]
    public function it_updates_task_without_modifying_tags_when_not_provided(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'version' => 1,
        ]);
        $existingTags = Tag::factory()->count(2)->create();
        $task->tags()->attach($existingTags);

        $data = [
            'title' => 'Updated Title Only',
        ];

        $updatedTask = $this->taskService->updateTask($task, $data);

        // Tags should remain unchanged
        $this->assertCount(2, $updatedTask->fresh()->tags);
    }

    #[Test]
    public function it_searches_tasks_with_filters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'pending',
            'priority' => 'high',
        ]);
        Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'completed',
            'priority' => 'low',
        ]);

        $request = Request::create('/tasks', 'GET', [
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $result = $this->taskService->searchTasks($request);

        $this->assertGreaterThanOrEqual(1, $result->total());
    }
}
