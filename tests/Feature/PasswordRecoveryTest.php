<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'email' => 'rec@test.com',
            'password_hash' => bcrypt('old-password'),
            'secret_question' => 'Кличка питомца?',
            'secret_answer_hash' => bcrypt('барбос'),
        ]);
    }

    public function test_recover_question_returns_question(): void
    {
        $this->makeUser();
        $res = $this->postJson('/api/v1/auth/recover-question', ['email' => 'rec@test.com']);
        $res->assertStatus(200)->assertJson(['question' => 'Кличка питомца?']);
    }

    public function test_recover_question_generic_error_for_unknown_or_no_secret(): void
    {
        User::factory()->create(['email' => 'plain@test.com']);
        $this->postJson('/api/v1/auth/recover-question', ['email' => 'plain@test.com'])->assertStatus(400);
        $this->postJson('/api/v1/auth/recover-question', ['email' => 'ghost@test.com'])->assertStatus(400);
    }

    public function test_recover_reset_wrong_answer_rejected(): void
    {
        $this->makeUser();
        $res = $this->postJson('/api/v1/auth/recover-reset', [
            'email' => 'rec@test.com',
            'answer' => 'неправильно',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $res->assertStatus(400);
    }

    public function test_recover_reset_success_and_login_with_new_password(): void
    {
        $this->makeUser();
        $res = $this->postJson('/api/v1/auth/recover-reset', [
            'email' => 'rec@test.com',
            'answer' => '  Барбос ',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $res->assertStatus(200);
        $this->postJson('/api/v1/auth/login', ['email' => 'rec@test.com', 'password' => 'old-password'])->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['email' => 'rec@test.com', 'password' => 'new-password'])->assertStatus(200);
    }

    public function test_register_with_secret(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'email' => 'sec@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'secret_question' => 'Вопрос?',
            'secret_answer' => 'Ответ',
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'sec@test.com', 'secret_question' => 'Вопрос?']);
        $this->postJson('/api/v1/auth/recover-question', ['email' => 'sec@test.com'])
            ->assertStatus(200)->assertJson(['question' => 'Вопрос?']);
    }
}
