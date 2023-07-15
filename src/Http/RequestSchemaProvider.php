<?php

namespace TenantCloud\GraphQLPlatform\Http;

use Illuminate\Http\Request;
use TheCodingMachine\GraphQLite\Schema;

interface RequestSchemaProvider
{
	public function __invoke(Request $request): Schema;
}
