<?php

namespace Tests\Fixtures\Valid\Models;

use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SelectionResponse
{
	public function __construct(
		/** @var OffsetConnection<User> */
		#[Field] public readonly OffsetConnection $users,
		/** @var mixed */
		#[Field] public readonly mixed $selection,
	) {}
}
