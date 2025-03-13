<?php

namespace Tests\Fixtures\Controllers;

use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;
use Tests\Fixtures\Models\Nested;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ResolveController
{
	#[Query(outputType: 'Any!')]
	public function resolveKey(ResolveKey $key, bool $arg1, Nested $arg2): mixed
	{
		return [
			'type'  => $key->typeName,
			'field' => $key->fieldName,
			'args'  => $key->args,
		];
	}
}
