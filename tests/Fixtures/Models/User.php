<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Utils\Cloneable;

#[Type]
class User
{
	use Cloneable;

	public function __construct(
		#[Field] public readonly string $name,
		#[Field] public readonly CarbonImmutable $createdAt,
		#[Field] #[Cost(3)] public readonly ?CarbonInterval $somethingAfter = null,
		/** @var array<int> $fileIds */
		#[Field] public readonly array $fileIds = [],
	) {}

	public static function dummy(): self
	{
		return new self(
			name: 'Alex',
			createdAt: CarbonImmutable::create(2020, 1, 3),
			somethingAfter: CarbonInterval::hour(),
		);
	}
}
