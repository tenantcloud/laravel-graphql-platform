<?php

namespace TenantCloud\GraphQLPlatform\Http;

use GraphQL\Type\Schema;
use Illuminate\Http\Request;

interface RequestSchemaProvider
{
	public function __invoke(Request $request): Schema;
}
