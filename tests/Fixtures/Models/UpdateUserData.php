<?php

namespace Tests\Fixtures\Models;

use Carbon\CarbonInterval;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\Constraints\Valid;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Scalars\ID\ID;
use Tests\Fixtures\Validation\PersonName;
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
	#[PersonName]
	public string|MissingValue           $name = MissingValue::INSTANCE;

	#[Field]
	public CarbonInterval|MissingValue|null  $somethingAfter = MissingValue::INSTANCE;

	/** @var array<string> */
	#[Field]
	#[ID]
	#[AtLeastOneOf([
		new Unique(),
		new EqualTo([123, 9999999999, 9999999999, 9999999999, 9999999999]),
	])]
	public array $fileIds = [];

	/** @var array<Nested>|MissingValue */
	#[Field(name: 'nested')]
	#[Valid]
	public mixed $nest = MissingValue::INSTANCE;
}
