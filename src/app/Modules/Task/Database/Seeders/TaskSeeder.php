<?php
declare(strict_types=1);

namespace App\Modules\Task\Database\Seeders;

use App\Models\User;
use App\Modules\Task\Models\Tag;
use App\Modules\Task\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $tags = Tag::all();

        for($i=0; $i < 100; $i++) {
            $user = $users->random();
            $task = Task::factory()->for($user, 'user')->create();

            // Pick 3 to 5 unique tags
            $randomTags = $tags->random(rand(3, 5));

            // Attach tags ensuring uniqueness in pivot
            $task->tags()->syncWithoutDetaching($randomTags->pluck('id')->toArray());
        }
    }
}
