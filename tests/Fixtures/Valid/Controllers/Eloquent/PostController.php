<?php

namespace Tests\Fixtures\Valid\Controllers\Eloquent;

use Carbon\CarbonInterval;
use TenantCloud\GraphQLPlatform\Laravel\Database\Transactional;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use Tests\Fixtures\Valid\Models\Eloquent\Data\CreatePostData;
use Tests\Fixtures\Valid\Models\Eloquent\Data\UpdatePostData;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class PostController
{
	#[Mutation]
	#[Transactional]
	public function createPost(CreatePostData $data): Post
	{
		$post = new Post();
		$post->blog()->associate($data->blog);
		$post->content = $data->content;
		$post->save();

		return $post;
	}

	#[Mutation]
	#[Transactional]
	public function updatePost(
		UpdatePostData $data,
		ValidationExceptions $validationExceptions,
	): Post {
		if ($data->readTime !== MissingValue::INSTANCE && $data->readTime->equalTo(CarbonInterval::hours(1337))) {
			throw $validationExceptions->forProperty('data', $data, 'readTime', ['Cannot be equal to 1337 hours :/']);
		}

		if ($data->content !== MissingValue::INSTANCE) {
			$data->post->content = $data->content;
		}

		if ($data->readTime !== MissingValue::INSTANCE) {
			$data->post->read_time = $data->readTime;
		}

		$data->post->save();

		return $data->post;
	}

	#[Mutation]
	#[Transactional]
	public function deletePost(Post $post): void
	{
		$post->delete();
	}
}
