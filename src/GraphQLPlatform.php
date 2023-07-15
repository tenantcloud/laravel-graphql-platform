<?php

namespace TenantCloud\GraphQLPlatform;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;

final class GraphQLPlatform
{
	public const NAMESPACE = 'graphql_platform';

	public function __construct(
		private readonly Router $router,
		private readonly UrlGenerator $urlGenerator,
	) {}

	public static function namespaced(string $item): string
	{
		return self::NAMESPACE . '::' . $item;
	}
}
