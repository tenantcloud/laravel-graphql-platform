<?php

namespace TenantCloud\GraphQLPlatform\Resolve;

use GraphQL\Type\Definition\ResolveInfo;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ResolveKeyParameter implements ParameterInterface
{
	public function resolve(object|null $source, array $args, mixed $context, ResolveInfo $info): ResolveKey
	{
		return new ResolveKey(
			$info->parentType->name(),
			$info->fieldName,
			$args,
		);
	}
}
