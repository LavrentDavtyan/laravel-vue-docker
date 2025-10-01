<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelperOverspendTest extends TestCase
{
    use RefreshDatabase;

    public function test_overspend_endpoint_returns_cards(): void
    {
        $user = User::factory()->create();

        // Seed 5 weeks of data; make current week higher for 'Food'
        $today = Carbon::today();
        $startOfWeek = (clone $today)->startOfWeek(Carbon::SUNDAY);

        // 4 previous weeks baseline (~100 each)
        for ($w = 1; $w <= 4; $w++) {
            $weekStart = (clone $startOfWeek)->subWeeks($w);
            Expense::factory()->for($user)->create([
                'category' => 'Food',
                'amount'   => 100,
                'date'     => $weekStart->toDateString(),
            ]);
        }

        // Current week: spend more (e.g., 200)
        Expense::factory()->for($user)->create([
            'category' => 'Food',
            'amount'   => 200,
            'date'     => $startOfWeek->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum');
        $res = $this->getJson('/api/helper/overspend?period=week');

        $res->assertOk();
        $data = $res->json();
        $this->assertIsArray($data);
        $this->assertTrue(collect($data)->contains(fn($c) => ($c['category'] ?? null) === 'Food'));
    }
}
