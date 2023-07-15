<?php

namespace TenantCloud;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use TenantCloud\GraphQLPlatform\Http\DefaultRequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Http\RequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TheCodingMachine\GraphQLite\Schema;

final class GraphQLPlatform
{
	public const NAMESPACE = 'graphql_platform';

	public function __construct(
		private readonly Router $router,
		private readonly UrlGenerator $urlGenerator,
	)
	{
	}

	public static function namespaced(string $item): string
	{
		return self::NAMESPACE . '::' . $item;
	}
}
