<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_budget_per_month_and_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $month = Carbon::now()->startOfMonth()->toDateString();

        $this->postJson('/api/budgets', [
            'category' => 'Food',
            'month' => $month,
            'amount_decimal' => 100,
            'currency' => 'USD'
        ])->assertCreated();

        $this->postJson('/api/budgets', [
            'category' => 'Food',
            'month' => $month,
            'amount_decimal' => 200,
            'currency' => 'USD'
        ])->assertStatus(422);
    }

    public function test_stats_returns_spend_and_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $month = Carbon::now()->startOfMonth();
        Budget::create([
            'user_id' => $user->id,
            'category' => 'Transport',
            'month' => $month->toDateString(),
            'amount_decimal' => 100,
            'currency' => 'USD',
        ]);
        Expense::create([
            'user_id' => $user->id,
            'category' => 'Transport',
            'amount' => 60,
            'date' => $month->copy()->addDays(3)->toDateString(),
        ]);

        $res = $this->getJson('/api/budgets/stats?month='.$month->toDateString())
            ->assertOk()
            ->json();

        $row = collect($res)->firstWhere('category', 'Transport');
        $this->assertEquals(60, (int) $row['spend']);
        $this->assertEquals('Under', $row['status']);
    }
}
