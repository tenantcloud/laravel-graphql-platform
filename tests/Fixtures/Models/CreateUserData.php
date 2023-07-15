<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class CreateUserData
{
	public function __construct(
		#[Field] public readonly string $name,
		#[Field] public readonly CarbonImmutable $createdAt,
		#[Field] public readonly CarbonInterval $somethingAfter,
	) {}
}
