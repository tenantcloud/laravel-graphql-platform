<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelID;
use TenantCloud\GraphQLPlatform\MissingValue;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class UpdateCommentData
{
	public function __construct(
		#[Field]
		#[ModelID(lockForUpdate: true)]
		public readonly Comment $comment,
		#[Field]
		public readonly Comment|null|MissingValue $parent = MissingValue::INSTANCE,
	) {}
}
