<?php

namespace Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use Tests\Fixtures\Valid\Models\Eloquent\User;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
	protected $model = Comment::class;

	public function definition(): array
	{
		return [
			'content' => $this->faker->realText,
			'rating'  => $this->faker->optional()->randomFloat(1, 1, 5),
		];
	}

	public function forAuthor(User|UserFactory $factory): self
	{
		return $this->for($factory, 'author');
	}

	public function newAuthor(): self
	{
		return $this->forAuthor(UserFactory::new());
	}

	public function forPost(PostFactory|Post $factory): self
	{
		return $this->for($factory, 'post');
	}

	public function newPost(): self
	{
		return $this->forPost(
			PostFactory::new()
				->newBlog()
		);
	}

	public function forParent(self|Comment $factory): self
	{
		return $this->for($factory, 'parent');
	}
}
