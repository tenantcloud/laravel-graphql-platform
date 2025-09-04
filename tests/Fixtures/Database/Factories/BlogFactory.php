<?php

namespace Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
	protected $model = Blog::class;

	public function definition(): array
	{
		return [
			'name' => $this->faker->title,
		];
	}

	public function hasPosts(PostFactory $factory): self
	{
		return $this->has($factory, 'posts');
	}
}
