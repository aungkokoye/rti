<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Models\User;
use App\Modules\Task\Models\Tag;
use App\Modules\Task\Models\Task;
use App\Traits\CanLoadRelations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CanLoadRelationsTest extends TestCase
{
    use RefreshDatabase;

    private object $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test service that uses the trait
        $this->service = new class {
            use CanLoadRelations;

            public array $relations = ['user', 'tags'];
        };
    }

    #[Test]
    public function it_loads_single_relation_on_model_when_requested(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Simulate request with include parameter
        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user']));

        $result = $this->service->loadRelations($task);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertFalse($result->relationLoaded('tags'));
        $this->assertInstanceOf(User::class, $result->user);
    }

    #[Test]
    public function it_loads_multiple_relations_on_model_when_requested(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $tags = Tag::factory()->count(2)->create();
        $task->tags()->attach($tags);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,tags']));

        $result = $this->service->loadRelations($task);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('tags'));
        $this->assertCount(2, $result->tags);
    }

    #[Test]
    public function it_does_not_load_relations_when_include_is_empty(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET'));

        $result = $this->service->loadRelations($task);

        $this->assertFalse($result->relationLoaded('user'));
        $this->assertFalse($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_does_not_load_disallowed_relations(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Request a relation that is not in allowed list
        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'disallowed_relation']));

        $result = $this->service->loadRelations($task);

        $this->assertFalse($result->relationLoaded('user'));
        $this->assertFalse($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_loads_relations_on_query_builder(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,tags']));

        $query = Task::query();
        $result = $this->service->loadRelations($query);

        $tasks = $result->get();

        foreach ($tasks as $task) {
            $this->assertTrue($task->relationLoaded('user'));
            $this->assertTrue($task->relationLoaded('tags'));
        }
    }

    #[Test]
    public function it_handles_relations_with_spaces_in_include_parameter(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $tags = Tag::factory()->count(2)->create();
        $task->tags()->attach($tags);

        // Include parameter with spaces
        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user, tags']));

        $result = $this->service->loadRelations($task);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_uses_custom_relations_array_when_provided(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user']));

        // Pass custom relations array that only includes 'user'
        $result = $this->service->loadRelations($task, ['user']);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertFalse($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_returns_same_instance_type(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user']));

        $result = $this->service->loadRelations($task);

        $this->assertInstanceOf(Task::class, $result);
        $this->assertSame($task, $result);
    }

    #[Test]
    public function it_handles_empty_allowed_relations(): void
    {
        $service = new class {
            use CanLoadRelations;

            public array $relations = [];
        };

        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,tags']));

        $result = $service->loadRelations($task);

        $this->assertFalse($result->relationLoaded('user'));
        $this->assertFalse($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_loads_only_allowed_relations_from_request(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        // Request includes both allowed and disallowed relations
        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,invalid,tags']));

        $result = $this->service->loadRelations($task);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('tags'));
    }

    #[Test]
    public function it_works_with_has_many_relationship(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['assigned_to' => $user->id]);

        $this->app->instance('request', Request::create('/tasks', 'GET', ['include' => 'user,tags']));

        // Test with HasMany relationship
        $hasManyRelation = $user->tasks();
        $result = $this->service->loadRelations($hasManyRelation);

        $tasks = $result->get();

        foreach ($tasks as $task) {
            $this->assertTrue($task->relationLoaded('user'));
            $this->assertTrue($task->relationLoaded('tags'));
        }
    }
}