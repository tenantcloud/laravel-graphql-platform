<?php

namespace Tests\Fixtures\Valid\Controllers\Eloquent;

use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelID;
use TenantCloud\GraphQLPlatform\Laravel\Database\Transactional;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;
use Tests\Fixtures\Valid\Models\Eloquent\Data\CreateCommentData;
use Tests\Fixtures\Valid\Models\Eloquent\Data\UpdateCommentData;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Subscription;

class CommentController
{
	#[Mutation]
	#[Transactional]
	public function createComment(CreateCommentData $data): Comment
	{
		$comment = new Comment();
		$comment->post()->associate($data->post);
		$comment->parent()->associate($data->parent);
		$comment->content = $data->content;
		$comment->save();

		return $comment;
	}

	#[Mutation]
	#[Transactional]
	public function updateComment(UpdateCommentData $data): Comment
	{
		$comment = $data->comment;

		if ($data->parent !== MissingValue::INSTANCE) {
			$comment->parent()->associate($data->parent);
		}

		$comment->save();

		return $comment;
	}

	#[Mutation]
	#[Transactional]
	public function deleteComment(
		#[ModelID(lockForUpdate: true)] Comment $comment
	): void {
		$comment->delete();
	}

	/**
	 * @return ChannelSubscription<Comment>
	 */
	#[Subscription]
	public function commentCreated(
		#[ModelID] Post $post,
	): ChannelSubscription {
		return new ChannelSubscription(
			"posts:{$post->id}:comments:created",
		);
	}
}
