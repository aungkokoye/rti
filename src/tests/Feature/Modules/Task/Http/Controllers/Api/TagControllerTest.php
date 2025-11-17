<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Task\Http\Controllers\Api;

use App\Models\User;
use App\Modules\Task\Models\Tag;
use App\Modules\Task\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private string $userToken;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->userToken = $this->user->createToken('api-token')->plainTextToken;
        $this->adminToken = $this->admin->createToken('api-token')->plainTextToken;
    }

    #[Test]
    public function user_can_list_tags(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[Test]
    public function admin_can_list_tags(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->getJson('/api/tags');

        $response->assertStatus(401);
    }

    #[Test]
    public function admin_can_create_tag(): void
    {
        $tagData = [
            'name' => 'New Tag',
            'color' => '#FF5733',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/tags', $tagData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Tag');

        $this->assertDatabaseHas('tags', ['name' => 'New Tag']);
    }

    #[Test]
    public function user_cannot_create_tag(): void
    {
        $tagData = [
            'name' => 'New Tag',
            'color' => '#FF5733',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson('/api/tags', $tagData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('tags', ['name' => 'New Tag']);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/tags', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    }

    #[Test]
    public function store_validates_unique_name(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/tags', [
                'name' => 'Existing Tag',
                'color' => '#FF5733',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function admin_can_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/tags/{$tag->id}", [
                'name' => 'Updated Name',
                'color' => '#00FF00',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function user_cannot_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->putJson("/api/tags/{$tag->id}", [
                'name' => 'Updated Name',
                'color' => '#00FF00',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Old Name',
        ]);
    }

    #[Test]
    public function update_validates_required_fields(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/tags/{$tag->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    }

    #[Test]
    public function admin_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    #[Test]
    public function user_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    #[Test]
    public function destroy_requires_authentication(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(401);
    }

    #[Test]
    public function tag_can_have_tasks_relationship(): void
    {
        $tag = Tag::factory()->create();
        $tasks = Task::factory()->count(3)->create();

        $tag->tasks()->attach($tasks->pluck('id'));

        $this->assertCount(3, $tag->tasks);
        $this->assertInstanceOf(Task::class, $tag->tasks->first());
    }

    #[Test]
    public function tag_tasks_relationship_includes_timestamps(): void
    {
        $tag = Tag::factory()->create();
        $task = Task::factory()->create();

        $tag->tasks()->attach($task->id);

        $pivotData = $tag->tasks->first()->pivot;
        $this->assertNotNull($pivotData->created_at);
        $this->assertNotNull($pivotData->updated_at);
    }
}
