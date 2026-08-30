<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Veldora\Framework\Database\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected string $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'name' => 'Sample Name',
            // 'email' => 'user' . rand(100, 999) . '@example.com',
        ];
    }
}