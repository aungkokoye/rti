<?php
declare(strict_types=1);

namespace App\Modules\Task\Database\Seeders;

use App\Modules\Task\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tag::factory(10)->create();
    }
}
