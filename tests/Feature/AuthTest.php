<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('user_progress', ['lives' => 5, 'coins' => 500]);
    }

    public function test_register_validation_fail(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'email' => 'invalid',
            'password' => 'short',
        ]);
        $res->assertStatus(422);
    }

    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'login@test.com',
            'password_hash' => bcrypt('secret123'),
        ]);
        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@test.com',
            'password' => 'secret123',
        ]);
        $res->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong@test.com',
            'password_hash' => bcrypt('correct'),
        ]);
        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'incorrect',
        ]);
        $res->assertStatus(422);
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');
        $res->assertStatus(204);
    }

    public function test_protected_route_without_token(): void
    {
        $res = $this->getJson('/api/v1/user/profile');
        $res->assertStatus(401);
    }
}