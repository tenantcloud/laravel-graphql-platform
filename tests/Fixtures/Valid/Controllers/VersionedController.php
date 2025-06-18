<?php

namespace Tests\Fixtures\Valid\Controllers;

use TenantCloud\GraphQLPlatform\Versioning\ForVersions;
use Tests\Fixtures\Valid\Models\VersionedInput;
use TheCodingMachine\GraphQLite\Annotations\Query;

class VersionedController
{
	#[Query]
	#[ForVersions('>=2')]
	public function versionedField(VersionedInput $data): string
	{
		return 'v2';
	}

	#[Query(name: 'versionedField')]
	#[ForVersions('<=1.0')]
	public function versionedFieldV1(VersionedInput $data): int
	{
		return 1;
	}
}
