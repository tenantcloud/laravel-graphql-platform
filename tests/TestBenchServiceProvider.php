<?php

namespace Tests;

use Illuminate\Support\ServiceProvider;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use Tests\Fixtures\TypeMappers\AnyRootTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

class TestBenchServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->extend(
			SchemaConfigurator::class,
			fn (SchemaConfigurator $configurator) => $configurator
				->addNamespace('Tests\\Fixtures')
				->addRootTypeMapperFactory(new class () implements RootTypeMapperFactoryInterface {
					public function create(RootTypeMapperInterface $next, RootTypeMapperFactoryContext $context): RootTypeMapperInterface
					{
						return new AnyRootTypeMapper($next);
					}
				})
		);

		$this->app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->addDefaultSchema(fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
		);
	}
}
