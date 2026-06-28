<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1; // عداد داخلي يبدأ من 1 ويتصاعد تلقائياً

        return [
            'first_name' => fake()->name(),
            // هذا السطر يضمن توليد ايميلات متسلسلة تبدأ من user1@test.com، user2@test.com تصاعدياً
            'email' => 'user' . $sequence++ . '@test.com',
            'password' => Hash::make('password123'), // توحيد كلمة المرور لتطابق الـ Seeder و JMeter
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}