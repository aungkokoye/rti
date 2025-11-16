<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Task\Http\Controllers\Api;

use App\Models\User;
use App\Modules\Task\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    #[Test]
    public function index_returns_paginated_tags(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
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
    public function store_creates_tag(): void
    {
        $tagData = [
            'name' => 'New Tag',
            'color' => '#FF5733',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tags', $tagData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Tag');

        $this->assertDatabaseHas('tags', ['name' => 'New Tag']);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tags', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    }

    #[Test]
    public function store_validates_unique_name(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tags', [
                'name' => 'Existing Tag',
                'color' => '#FF5733',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function update_modifies_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
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
    public function update_validates_required_fields(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/tags/{$tag->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    }

    #[Test]
    public function destroy_deletes_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    #[Test]
    public function destroy_requires_authentication(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(401);
    }
}