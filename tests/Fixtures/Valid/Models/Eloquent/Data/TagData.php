<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use Symfony\Component\Validator\Constraints\Length;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class TagData
{
	public function __construct(
		#[Field]
		#[Length(min: 1, max: 20)]
		public readonly string $name,
	) {}
}
