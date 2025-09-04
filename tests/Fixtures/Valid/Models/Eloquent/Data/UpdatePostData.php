<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use Carbon\CarbonInterval;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelID;
use TenantCloud\GraphQLPlatform\MissingValue;
use Tests\Fixtures\Valid\Models\Eloquent\Post;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class UpdatePostData
{
	public function __construct(
		#[Field]
		#[ModelID(lockForUpdate: true)]
		public readonly Post $post,
		#[Field]
		#[Length(min: 1, max: 255)]
		#[NotEqualTo(value: 'Trash')]
		public readonly string|MissingValue $content = MissingValue::INSTANCE,
		#[Field(name: 'readTime')]
		// TODO: rename to `$readTimeRenamed` once a fix for this is merged in GraphQLite
		public readonly CarbonInterval|MissingValue $readTime = MissingValue::INSTANCE,
	) {}
}
