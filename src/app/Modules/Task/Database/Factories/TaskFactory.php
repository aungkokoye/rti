<?php
declare(strict_types=1);

namespace App\Modules\Task\Database\Factories;

use app\Modules\Task\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'         => fake()->sentence(3),
            'description'   => fake()->paragraph(2, true),
            'status'        => $status = collect(Task::$status)->random(),
            'priority'      => collect(Task::$priority)->random(),
            'version'       => 1,
            'metadata'      => json_encode([
                                    'location'    => fake()->city,
                                    'link'        => fake()->url,
                                    'uuid'        => fake()->uuid
                                ]),

            'due_date'     => $status === 'completed'
                ? fake()->dateTimeBetween('-2 months', '-1 month') // past date
                : fake()->dateTimeBetween('+1 month', '+2 months'),

        ];
    }
}
