<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonInterval;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Scalars\ID\ID;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class UpdateUserData
{
	#[Field]
	#[ID]
	public string           $id;

	#[Field]
	public string|MissingValue           $name = MissingValue::INSTANCE;

	#[Field]
	public CarbonInterval|MissingValue|null  $somethingAfter = MissingValue::INSTANCE;

	/** @var array<string> */
	#[Field]
	#[ID]
	public array $fileIds = [];
}
