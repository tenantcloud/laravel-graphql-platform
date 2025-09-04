<?php

namespace Tests\Fixtures\Valid\Controllers;

use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;
use Tests\Fixtures\Valid\Models\Eloquent\Data\TagData;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ResolveController
{
	#[Query(outputType: 'Any!')]
	public function resolveKey(ResolveKey $key, bool $arg1, TagData $arg2): mixed
	{
		return [
			'type'  => $key->typeName,
			'field' => $key->fieldName,
			'args'  => $key->args,
			'hash'  => $key->hash(),
		];
	}
}
