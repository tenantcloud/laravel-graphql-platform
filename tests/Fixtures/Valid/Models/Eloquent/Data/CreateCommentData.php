<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelID;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class CreateCommentData
{
	public function __construct(
		#[Field]
		#[ModelID(lockForUpdate: true)]
		public readonly Post $post,
		#[Field]
		#[ModelID(lockForUpdate: true)]
		public readonly ?Comment $parent,
		#[Field] public readonly string $content,
	) {}
}
