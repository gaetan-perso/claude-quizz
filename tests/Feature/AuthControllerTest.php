<?php declare(strict_types=1);

use App\Models\User;

describe('POST /api/v1/auth/register', function () {
    it('registers a new user and returns token', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Alice',
            'email'                 => 'alice@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'alice@example.com')
            ->assertJsonPath('data.user.role', 'player')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    });

    it('fails with duplicate email', function () {
        User::factory()->create(['email' => 'bob@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Bob',
            'email'                 => 'bob@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    });

    it('fails with password mismatch', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Carol',
            'email'                 => 'carol@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/auth/login', function () {
    it('logs in with valid credentials and returns token', function () {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    });

    it('fails with wrong password', function () {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Identifiants incorrects.');
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('deletes the current token', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('mobile');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)->assertJsonPath('data', null);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
    });
});

describe('GET /api/v1/auth/me', function () {
    it('returns current user', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    });
});
