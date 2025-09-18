<?php

namespace Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use Tests\Fixtures\Valid\Models\Eloquent\User;

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

	public function forOwner(User|UserFactory $factory): self
	{
		return $this->for($factory, 'owner');
	}

	public function newOwner(): self
	{
		return $this->forOwner(UserFactory::new());
	}

	public function hasPosts(PostFactory $factory): self
	{
		return $this->has($factory, 'posts');
	}
}
