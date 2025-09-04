<?php

namespace Tests\Fixtures\Valid\Models;

use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SelectionResponse
{
	public function __construct(
		/** @var OffsetConnection<Blog> */
		#[Field] public readonly OffsetConnection $blogs,
		/** @var mixed */
		#[Field] public readonly mixed $selection,
	) {}
}
