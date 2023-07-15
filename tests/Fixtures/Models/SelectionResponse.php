<?php

namespace Tests\Fixtures\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SelectionResponse
{
	public function __construct(
		/** @var LengthAwarePaginator<User> */
		#[Field] public readonly LengthAwarePaginator $users,
		/** @var mixed */
		#[Field] public readonly mixed $selection,
	)
	{
	}
}
