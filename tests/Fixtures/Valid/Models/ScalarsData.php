<?php

namespace Tests\Fixtures\Valid\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use TenantCloud\GraphQLPlatform\Scalars\Hints\Date;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
#[Input]
class ScalarsData
{
	public function __construct(
		#[Field] public readonly string $string,
		#[Field] public readonly CarbonImmutable $dateTime,
		#[Field] public readonly CarbonInterval $duration,
		#[Field] #[Date] public readonly ?CarbonImmutable $date = null,
	) {}
}
