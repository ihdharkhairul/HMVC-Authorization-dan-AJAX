<?php

namespace Modules\Pendaftaran\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pendaftaran\Models\Daftar;

/**
 * @extends Factory<Daftar>
 */
class DaftarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Daftar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'asal_sekolah' => 'SMA '.fake()->city(),
            'prodi_tujuan' => fake()->randomElement(['Informatika', 'Sistem Informasi', 'Teknik Elektro']),
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => fake()->numerify('08##########'),
        ];
    }
}
