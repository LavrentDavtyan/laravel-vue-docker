<?php

namespace Tests\Feature;

use App\Models\ShareExpense;
use App\Models\ShareExpenseSplit;
use App\Models\ShareTopic;
use Database\Seeders\DemoShareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function demo_seeder_creates_topic_members_and_expenses(): void
    {
        $this->seed(DemoShareSeeder::class);

        $topic = ShareTopic::first();
        $this->assertNotNull($topic);
        $this->assertEquals('Demo Trip', $topic->title);

        $this->assertCount(3, $topic->members);
        $this->assertCount(3, $topic->expenses);

        /** @var ShareExpense $e1 */
        $e1 = $topic->expenses()->where('description', 'Groceries')->first();
        $this->assertNotNull($e1);
        $this->assertEquals('90.00', $e1->amount_decimal);

        $splitsCount = ShareExpenseSplit::where('share_expense_id', $e1->id)->count();
        $this->assertEquals(3, $splitsCount); // all three participated
    }
}
