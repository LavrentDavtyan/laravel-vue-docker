<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Category;
use App\Models\Budget;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ensure categories exist
        $food = Category::firstOrCreate(['name' => 'Food']);
        $transport = Category::firstOrCreate(['name' => 'Transport']);
        $month = Carbon::now()->startOfMonth()->toDateString();
        $u = $user->id ?? 1;

        // demo expenses
        Expense::factory()->count(10)->create([
            'category_id' => $food->id,
        ]);

        // demo incomes
        Income::factory()->count(5)->create();


        //budget
        Budget::updateOrCreate(
            ['user_id' => $u, 'category' => 'Food', 'month' => $month],
            ['amount_decimal' => 300, 'currency' => 'USD']
        );
        Budget::updateOrCreate(
            ['user_id' => $u, 'category' => 'Transport', 'month' => $month],
            ['amount_decimal' => 120, 'currency' => 'USD']
        );
    }
}
