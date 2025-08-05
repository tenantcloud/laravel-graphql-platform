<?php

namespace Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Fixtures\Valid\Models\Eloquent\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
	protected $model = User::class;

	public function definition(): array
	{
		static $password;

		if (!$password) {
			$password = Hash::make('password');
		}

		return [
			'name'              => fake()->name(),
			'email'             => fake()->unique()->safeEmail(),
			'email_verified_at' => now(),
			'password'          => $password,
			'remember_token'    => Str::random(10),
		];
	}
}
