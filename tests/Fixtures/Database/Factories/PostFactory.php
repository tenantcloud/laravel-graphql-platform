<?php

namespace Tests\Fixtures\Database\Factories;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use Tests\Fixtures\Valid\Models\Eloquent\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
	protected $model = Post::class;

	public function definition(): array
	{
		$readTimeSeconds = $this->faker->optional()->numberBetween(60, 30 * 60);

		return [
			'content'   => $this->faker->realText,
			'read_time' => $readTimeSeconds ? CarbonInterval::seconds($readTimeSeconds) : null,
		];
	}

	public function forBlog(BlogFactory|Blog $factory): self
	{
		return $this->for($factory, 'blog');
	}

	public function newBlog(): self
	{
		return $this->forBlog(BlogFactory::new());
	}
}
