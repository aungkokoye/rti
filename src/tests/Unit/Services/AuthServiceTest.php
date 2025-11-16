<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    #[Test]
    public function it_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertInstanceOf(NewAccessToken::class, $result);
        $this->assertNotEmpty($result->plainTextToken);
    }

    #[Test]
    public function it_creates_token_with_api_token_name(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertEquals('api-token', $result->accessToken->name);
    }

    #[Test]
    public function it_returns_error_for_non_existent_user(): void
    {
        $result = $this->authService->login([
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    #[Test]
    public function it_returns_error_for_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    #[Test]
    public function it_stores_token_in_database(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'api-token',
        ]);
    }

    #[Test]
    public function it_can_create_multiple_tokens_for_same_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $token1 = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token2 = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertNotEquals($token1->plainTextToken, $token2->plainTextToken);
        $this->assertEquals(2, $user->tokens()->count());
    }

    #[Test]
    public function it_deletes_all_tokens_on_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create multiple tokens
        $user->createToken('token1');
        $user->createToken('token2');
        $user->createToken('token3');

        $this->assertEquals(3, $user->tokens()->count());

        $request = Request::create('/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->authService->logout($request);

        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function it_only_deletes_current_user_tokens_on_logout(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->createToken('user1-token');
        $user2->createToken('user2-token');

        $request = Request::create('/logout', 'POST');
        $request->setUserResolver(fn () => $user1);

        $this->authService->logout($request);

        $this->assertEquals(0, $user1->tokens()->count());
        $this->assertEquals(1, $user2->tokens()->count());
    }
}
