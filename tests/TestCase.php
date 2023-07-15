<?php

namespace Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use Tests\Fixtures\TypeMappers\AnyRootTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

abstract class TestCase extends BaseTestCase
{
	use WithFaker;

	protected function setUp(): void
	{
		parent::setUp();

		$this->afterApplicationCreated(function () {
			$this->app->extend(
				SchemaConfigurator::class,
				fn (SchemaConfigurator $configurator) => $configurator
					->addTypeNamespace('Tests\\Fixtures')
					->addControllerNamespace('Tests\\Fixtures')
					->addRootTypeMapperFactory(new class () implements RootTypeMapperFactoryInterface {
						public function create(RootTypeMapperInterface $next, RootTypeMapperFactoryContext $context): RootTypeMapperInterface
						{
							return new AnyRootTypeMapper($next);
						}
					})
			);
		});
	}

	protected function getPackageProviders($app): array
	{
		return [
			GraphQLPlatformServiceProvider::class,
		];
	}

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app['config']->set('app.debug', true);
	}
}
