<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class CreateBlogData
{
	public function __construct(
		#[Field] public readonly string $name,
	) {}
}
