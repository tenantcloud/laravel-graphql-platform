<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use Symfony\Component\Validator\Constraints\Cascade;
use Symfony\Component\Validator\Constraints\Count;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
#[Cascade]
class CreatePostData
{
	/**
	 * @param list<TagData> $tags
	 */
	public function __construct(
		#[Field]
		public readonly Blog $blog,
		#[Field] public readonly string $content,
		#[Field]
		#[Count(max: 5)]
		public readonly array $tags,
	) {}
}
