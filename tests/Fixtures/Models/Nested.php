<?php

namespace Tests\Fixtures\Models;

use Symfony\Component\Validator\Constraints\Length;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class Nested
{
	public function __construct(
		#[Field]
		#[Length(min: 3, max: 4)]
		public readonly string $name,
	) {}
}
