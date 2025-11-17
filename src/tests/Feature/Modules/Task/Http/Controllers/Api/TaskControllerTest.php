<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Task\Http\Controllers\Api;

use App\Models\User;
use App\Modules\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    #[Test]
    public function index_returns_paginated_tasks(): void
    {
        Task::factory()->count(3)->create(['assigned_to' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    #[Test]
    public function store_creates_task(): void
    {
        $taskData = [
            'title' => 'New Task Title',
            'description' => 'Task description',
            'status' => 'pending',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
            'metadata' => [
                'location' => 'Office',
                'link' => 'https://example.com/task',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Task Title');

        $this->assertDatabaseHas('tasks', ['title' => 'New Task Title']);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
    }

    #[Test]
    public function show_returns_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
    }

    #[Test]
    public function show_returns_403_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function update_modifies_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'title' => 'Original Title',
            'status' => 'pending',
            'priority' => 'low',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'status' => 'in_progress',
                'priority' => 'high',
                'metadata' => [
                    'location' => 'Office',
                    'link' => 'https://example.com/task',
                    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    }

    #[Test]
    public function update_returns_403_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'status' => 'pending',
                'priority' => 'low',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function destroy_deletes_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function destroy_returns_403_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function restore_restores_soft_deleted_task(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->user->id]);
        $task->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/tasks/{$task->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function restore_returns_403_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $otherUser->id]);
        $task->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/tasks/{$task->id}/restore");

        $response->assertStatus(403);
    }

    #[Test]
    public function toggle_status_changes_task_status(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/tasks/{$task->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    #[Test]
    public function toggle_status_returns_403_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/tasks/{$task->id}/toggle-status");

        $response->assertStatus(403);
    }
}
