<?php

namespace Tests\Fixtures\Valid\Notifications;

use Illuminate\Notifications\Notification;
use TenantCloud\GraphQLPlatform\Subscription\Notifications\GraphQLChannel;
use TenantCloud\GraphQLPlatform\Subscription\Notifications\GraphQLMessage;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;

class CommentCreatedNotification extends Notification
{
	public function __construct(
		private readonly Comment $comment,
	) {}

	public function via(): array
	{
		return [GraphQLChannel::class];
	}

	public function toGraphQL(): GraphQLMessage
	{
		return (new GraphQLMessage($this->comment))
			->on("posts:{$this->comment->id}:comments:created");
	}
}
