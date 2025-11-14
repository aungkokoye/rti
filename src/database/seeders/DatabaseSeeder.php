<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Modules\Task\Database\Seeders\TagSeeder;
use App\Modules\Task\Database\Seeders\TaskSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@rti.com',
            'password' => 'password',
            'role'  => 1
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@rti.com',
            'password' => 'password',
        ]);

        User::factory(30)->create();

        $this->call([
            TagSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
