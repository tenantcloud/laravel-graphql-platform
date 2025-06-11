<?php

namespace Tests\Fixtures\Valid\Models;

use TenantCloud\GraphQLPlatform\Versioning\ForVersions;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
readonly class VersionedInput
{
	public function __construct(
		#[Field(name: 'id')]
		#[ForVersions('>=2')]
		public string $id,
		#[Field(name: 'id')]
		#[ForVersions('<=1')]
		public int $idV1 = 0,
	) {}
}
