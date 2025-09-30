<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Category;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ensure categories exist
        $food = Category::firstOrCreate(['name' => 'Food']);
        $transport = Category::firstOrCreate(['name' => 'Transport']);

        // demo expenses
        Expense::factory()->count(10)->create([
            'category_id' => $food->id,
        ]);

        // demo incomes
        Income::factory()->count(5)->create();
    }
}
