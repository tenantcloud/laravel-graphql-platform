<?php

namespace TenantCloud\GraphQLPlatform;

final class GraphQLPlatform
{
	public const NAMESPACE = 'graphql_platform';

	public static function namespaced(string $item): string
	{
		return self::NAMESPACE . '::' . $item;
	}
}
