<?php

namespace Database\Seeders;

use App\Models\ShareExpense;
use App\Models\ShareExpenseSplit;
use App\Models\ShareTopic;
use App\Models\ShareTopicMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoShareSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users (safe: uses fake emails)
        $owner = User::firstOrCreate(
            ['email' => 'owner@demo.test'],
            ['name' => 'Owner Demo', 'password' => bcrypt('password')]
        );
        $anna  = User::firstOrCreate(
            ['email' => 'anna@demo.test'],
            ['name' => 'Anna Demo', 'password' => bcrypt('password')]
        );
        $ben   = User::firstOrCreate(
            ['email' => 'ben@demo.test'],
            ['name' => 'Ben Demo', 'password' => bcrypt('password')]
        );

        // Topic
        $topic = ShareTopic::create([
            'owner_user_id' => $owner->id,
            'title'         => 'Demo Trip',
            'currency'      => 'USD',
            'invite_token'  => Str::random(48),
            'status'        => 'open',
        ]);

        // Members
        $mOwner = ShareTopicMember::create([
            'topic_id'     => $topic->id,
            'user_id'      => $owner->id,
            'display_name' => $owner->name,
            'role'         => 'owner',
            'joined_at'    => now(),
        ]);
        $mAnna = ShareTopicMember::create([
            'topic_id'     => $topic->id,
            'user_id'      => $anna->id,
            'display_name' => $anna->name,
            'role'         => 'member',
            'joined_at'    => now(),
        ]);
        $mBen = ShareTopicMember::create([
            'topic_id'     => $topic->id,
            'user_id'      => $ben->id,
            'display_name' => $ben->name,
            'role'         => 'member',
            'joined_at'    => now(),
        ]);

        $today = Carbon::today();

        // Expense #1: Owner paid 90, split among all three (equal 3 ways)
        $e1 = ShareExpense::create([
            'topic_id'        => $topic->id,
            'payer_member_id' => $mOwner->id,
            'description'     => 'Groceries',
            'amount_decimal'  => 90.00,
            'date'            => $today->copy(),
            'notes'           => null,
        ]);
        foreach ([$mOwner, $mAnna, $mBen] as $m) {
            ShareExpenseSplit::create([
                'share_expense_id' => $e1->id,
                'member_id'        => $m->id,
                'share_type'       => 'equal',
                'share_value'      => 1, // included
            ]);
        }

        // Expense #2: Anna paid 60, split among Owner+Anna (2 ways)
        $e2 = ShareExpense::create([
            'topic_id'        => $topic->id,
            'payer_member_id' => $mAnna->id,
            'description'     => 'Taxi',
            'amount_decimal'  => 60.00,
            'date'            => $today->copy()->subDay(),
            'notes'           => null,
        ]);
        foreach ([$mOwner, $mAnna] as $m) {
            ShareExpenseSplit::create([
                'share_expense_id' => $e2->id,
                'member_id'        => $m->id,
                'share_type'       => 'equal',
                'share_value'      => 1,
            ]);
        }

        // Expense #3: Ben paid 30, split among all three (3 ways)
        $e3 = ShareExpense::create([
            'topic_id'        => $topic->id,
            'payer_member_id' => $mBen->id,
            'description'     => 'Snacks',
            'amount_decimal'  => 30.00,
            'date'            => $today->copy()->subDays(2),
            'notes'           => null,
        ]);
        foreach ([$mOwner, $mAnna, $mBen] as $m) {
            ShareExpenseSplit::create([
                'share_expense_id' => $e3->id,
                'member_id'        => $m->id,
                'share_type'       => 'equal',
                'share_value'      => 1,
            ]);
        }
    }
}
