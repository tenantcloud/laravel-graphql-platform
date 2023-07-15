<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonInterval;
use TenantCloud\GraphQLPlatform\ID\ID;
use TenantCloud\GraphQLPlatform\MissingValue;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class UpdateUserData
{
	#[Field]
	#[ID]
	public int           $id;

	#[Field]
	public string|MissingValue           $name = MissingValue::INSTANCE;

	#[Field]
	public CarbonInterval|MissingValue|null  $somethingAfter = MissingValue::INSTANCE;

	/** @var array<int> */
	#[Field]
	#[ID]
	public array $fileIds = [];
}
