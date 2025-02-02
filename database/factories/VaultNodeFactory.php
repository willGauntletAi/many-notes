<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VaultNode>
 */
final class VaultNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'parent_id' => null,
            'is_file' => true,
            'name' => fake()->words(3, true),
            'extension' => 'md',
            'content' => fake()->paragraph(),
        ];
    }
}
