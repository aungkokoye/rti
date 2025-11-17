<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\AuditLogJob;
use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_audit_log_entry(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );

        $job->handle();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'class_name' => Task::class,
            'class_id' => $task->id,
            'operation_type' => 'created',
        ]);
    }

    #[Test]
    public function it_stores_correct_user_id(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'updated'
        );

        $job->handle();

        $auditLog = AuditLog::first();
        $this->assertEquals($user->id, $auditLog->user_id);
    }

    #[Test]
    public function it_stores_correct_class_name(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'deleted'
        );

        $job->handle();

        $auditLog = AuditLog::first();
        $this->assertEquals(Task::class, $auditLog->class_name);
    }

    #[Test]
    public function it_stores_correct_class_id(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'restored'
        );

        $job->handle();

        $auditLog = AuditLog::first();
        $this->assertEquals($task->id, $auditLog->class_id);
    }

    #[Test]
    public function it_stores_serialized_data(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'title' => 'Test Task',
            'description' => 'Test Description',
        ]);

        $taskData = json_encode($task->toArray());

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            $taskData,
            'created'
        );

        $job->handle();

        $auditLog = AuditLog::first();

        // Compare decoded arrays instead of raw JSON strings (key ordering may differ)
        $expectedData = json_decode($taskData, true);
        $storedData = json_decode($auditLog->change_data, true);

        $this->assertEquals($expectedData['title'], $storedData['title']);
        $this->assertEquals($expectedData['description'], $storedData['description']);
        $this->assertEquals('Test Task', $storedData['title']);
        $this->assertEquals('Test Description', $storedData['description']);
    }

    #[Test]
    public function it_stores_correct_operation_type(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $operationTypes = ['created', 'updated', 'deleted', 'restored'];

        foreach ($operationTypes as $operationType) {
            $job = new AuditLogJob(
                (string) $user->id,
                Task::class,
                $task->id,
                json_encode($task->toArray()),
                $operationType
            );

            $job->handle();

            $this->assertDatabaseHas('audit_logs', [
                'class_id' => $task->id,
                'operation_type' => $operationType,
            ]);
        }
    }

    #[Test]
    public function it_implements_should_queue_interface(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    #[Test]
    public function it_uses_queueable_trait(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );

        $this->assertTrue(
            in_array(\Illuminate\Foundation\Queue\Queueable::class, class_uses_recursive($job))
        );
    }

    #[Test]
    public function it_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        AuditLogJob::dispatch(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );

        Queue::assertPushed(AuditLogJob::class);
    }

    #[Test]
    public function it_creates_multiple_audit_logs_for_same_entity(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Create
        $createJob = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode(['title' => 'Original']),
            'created'
        );
        $createJob->handle();

        // Update
        $updateJob = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode(['title' => 'Updated']),
            'updated'
        );
        $updateJob->handle();

        // Delete
        $deleteJob = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode(['title' => 'Updated']),
            'deleted'
        );
        $deleteJob->handle();

        $auditLogs = AuditLog::where('class_id', $task->id)->get();
        $this->assertCount(3, $auditLogs);

        $operations = $auditLogs->pluck('operation_type')->toArray();
        $this->assertContains('created', $operations);
        $this->assertContains('updated', $operations);
        $this->assertContains('deleted', $operations);
    }

    #[Test]
    public function it_stores_audit_log_with_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user1->id]);

        // User 1 creates
        $job1 = new AuditLogJob(
            (string) $user1->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );
        $job1->handle();

        // User 2 updates
        $job2 = new AuditLogJob(
            (string) $user2->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'updated'
        );
        $job2->handle();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user1->id,
            'operation_type' => 'created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user2->id,
            'operation_type' => 'updated',
        ]);
    }

    #[Test]
    public function it_handles_large_json_data(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'metadata' => [
                'key1' => str_repeat('value', 100),
                'key2' => str_repeat('data', 100),
                'nested' => [
                    'deep' => str_repeat('content', 50),
                ],
            ],
        ]);

        $largeData = json_encode($task->toArray());

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            $largeData,
            'created'
        );

        $job->handle();

        $auditLog = AuditLog::first();

        // Compare decoded arrays instead of raw JSON (key ordering may differ)
        $expectedData = json_decode($largeData, true);
        $storedData = json_decode($auditLog->change_data, true);

        $this->assertEquals($expectedData['metadata'], $storedData['metadata']);
        $this->assertNotEmpty($auditLog->change_data);
        $this->assertGreaterThan(1000, strlen($auditLog->change_data));
    }

    #[Test]
    public function it_can_track_different_model_types(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Track Task
        $taskJob = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );
        $taskJob->handle();

        // Track User
        $userJob = new AuditLogJob(
            (string) $user->id,
            User::class,
            $user->id,
            json_encode($user->toArray()),
            'updated'
        );
        $userJob->handle();

        $this->assertDatabaseHas('audit_logs', [
            'class_name' => Task::class,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'class_name' => User::class,
        ]);
    }

    #[Test]
    public function audit_log_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );
        $job->handle();

        $auditLog = AuditLog::first();
        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    #[Test]
    public function it_creates_audit_log_with_timestamps(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $job = new AuditLogJob(
            (string) $user->id,
            Task::class,
            $task->id,
            json_encode($task->toArray()),
            'created'
        );
        $job->handle();

        $auditLog = AuditLog::first();
        $this->assertNotNull($auditLog->created_at);
        $this->assertNotNull($auditLog->updated_at);
    }
}
