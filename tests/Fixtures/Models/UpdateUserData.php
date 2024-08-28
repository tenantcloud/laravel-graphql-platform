<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonInterval;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Unique;
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
	#[Length(min: 1, max: 255)]
	public string|MissingValue           $name = MissingValue::INSTANCE;

	#[Field]
	public CarbonInterval|MissingValue|null  $somethingAfter = MissingValue::INSTANCE;

	/** @var array<string> */
	#[Field]
	#[ID]
	#[AtLeastOneOf([
		new Unique(),
		new EqualTo([123]),
	])]
	public array $fileIds = [];
}
