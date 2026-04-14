<?php declare(strict_types=1);

use App\Models\Category;
use App\Models\Lobby;
use App\Models\User;

describe('POST /api/v1/lobbies', function () {
    it('creates a lobby and host is added as participant', function () {
        $user     = User::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lobbies', ['category_id' => $category->id]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.host_user_id', $user->id)
            ->assertJsonCount(1, 'data.participants')
            ->assertJsonStructure(['data' => ['id', 'code', 'status', 'max_players', 'category', 'participants']]);

        $this->assertDatabaseHas('lobbies', ['host_user_id' => $user->id]);
        $this->assertDatabaseHas('lobby_participants', ['user_id' => $user->id]);
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/lobbies')->assertStatus(401);
    });
});

describe('GET /api/v1/lobbies/{lobby}', function () {
    it('returns lobby details', function () {
        $user  = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/lobbies/{$lobby->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.code', $lobby->code);
    });
});

describe('POST /api/v1/lobbies/join', function () {
    it('allows a user to join with valid code', function () {
        $host  = User::factory()->create();
        $guest = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);

        $response = $this->actingAs($guest, 'sanctum')
            ->postJson('/api/v1/lobbies/join', ['code' => $lobby->code]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.participants');

        $this->assertDatabaseHas('lobby_participants', ['lobby_id' => $lobby->id, 'user_id' => $guest->id]);
    });

    it('returns 422 if lobby is full', function () {
        $host    = User::factory()->create();
        $player2 = User::factory()->create();
        $lobby   = Lobby::factory()->create(['host_user_id' => $host->id, 'max_players' => 2]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);
        $lobby->participants()->create(['user_id' => $player2->id, 'joined_at' => now()]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/lobbies/join', ['code' => $lobby->code])
            ->assertStatus(422);
    });

    it('returns 422 if already in lobby', function () {
        $user  = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $user->id]);
        $lobby->participants()->create(['user_id' => $user->id, 'joined_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lobbies/join', ['code' => $lobby->code])
            ->assertStatus(422);
    });

    it('returns 404 with invalid code', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lobbies/join', ['code' => 'XXXXXX'])
            ->assertStatus(404);
    });
});

describe('POST /api/v1/lobbies/{lobby}/leave', function () {
    it('removes participant from lobby', function () {
        $host  = User::factory()->create();
        $guest = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);
        $lobby->participants()->create(['user_id' => $guest->id, 'joined_at' => now()]);

        $this->actingAs($guest, 'sanctum')
            ->postJson("/api/v1/lobbies/{$lobby->id}/leave")
            ->assertStatus(200);

        $this->assertDatabaseMissing('lobby_participants', ['lobby_id' => $lobby->id, 'user_id' => $guest->id]);
    });

    it('host cannot leave', function () {
        $host  = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);

        $this->actingAs($host, 'sanctum')
            ->postJson("/api/v1/lobbies/{$lobby->id}/leave")
            ->assertStatus(422);
    });
});

describe('POST /api/v1/lobbies/{lobby}/start', function () {
    it('starts lobby and creates sessions for all participants', function () {
        $host  = User::factory()->create();
        $guest = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);
        $lobby->participants()->create(['user_id' => $guest->id, 'joined_at' => now()]);

        $response = $this->actingAs($host, 'sanctum')
            ->postJson("/api/v1/lobbies/{$lobby->id}/start");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('lobbies', ['id' => $lobby->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('quiz_sessions', ['user_id' => $host->id]);
        $this->assertDatabaseHas('quiz_sessions', ['user_id' => $guest->id]);
    });

    it('returns 403 if not host', function () {
        $host  = User::factory()->create();
        $guest = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $guest->id, 'joined_at' => now()]);

        $this->actingAs($guest, 'sanctum')
            ->postJson("/api/v1/lobbies/{$lobby->id}/start")
            ->assertStatus(403);
    });

    it('returns 422 with fewer than 2 players', function () {
        $host  = User::factory()->create();
        $lobby = Lobby::factory()->create(['host_user_id' => $host->id]);
        $lobby->participants()->create(['user_id' => $host->id, 'joined_at' => now()]);

        $this->actingAs($host, 'sanctum')
            ->postJson("/api/v1/lobbies/{$lobby->id}/start")
            ->assertStatus(422);
    });
});
