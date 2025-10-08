<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareCreateInviteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_topic_and_join_via_token(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        // Create topic
        $resp = $this->actingAs($u1, 'sanctum')->postJson('/api/share/topics', [
            'title' => 'Test Trip',
            'currency' => 'USD',
        ])->assertCreated();

        $topic = $resp->json('data');
        $this->assertNotEmpty($topic['invite_token']);

        // List topics for owner
        $this->actingAs($u1, 'sanctum')->getJson('/api/share/topics')
             ->assertOk()
             ->assertJsonPath('data.0.title', 'Test Trip');

        // u2 joins with token
        $this->actingAs($u2, 'sanctum')->postJson('/api/share/join/'.$topic['invite_token'])
             ->assertOk()
             ->assertJsonPath('data.topic_id', $topic['id']);
    }
}
