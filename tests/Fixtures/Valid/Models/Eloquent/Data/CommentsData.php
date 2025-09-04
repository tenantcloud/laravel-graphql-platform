<?php

namespace Tests\Fixtures\Valid\Models\Eloquent\Data;

use Symfony\Component\Validator\Constraints\Range;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class CommentsData
{
	public function __construct(
		#[Field]
		#[Range(min: 1, max: 5)]
		public readonly ?float $minRating = null,
	) {}
}
