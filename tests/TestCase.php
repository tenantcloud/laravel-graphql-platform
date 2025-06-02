<?php

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TenantCloud\APIVersioning\APIVersioningServiceProvider;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;
use Tests\Fixtures\TypeMappers\AnyRootTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use function Orchestra\Testbench\package_path;

abstract class TestCase extends BaseTestCase
{
	use WithFaker;
	use LazilyRefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();

		$this->afterApplicationCreated(function () {
			$this->app->extend(
				SchemaConfigurator::class,
				fn (SchemaConfigurator $configurator) => $configurator
					->usingComposerClassFinder(['Tests\\Fixtures'])
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
					->useSubscriptionTransport(new FakeSubscriptionTransport())
			);

			$this->app->booting(function () {
				$subscriptionTransportManager = $this->app->make(SubscriptionTransportManager::class);
				$subscriptionTransportManager->extend(FakeSubscriptionTransport::TYPE, fn () => new FakeSubscriptionTransport());
			});
		});
	}

	protected function getPackageProviders($app): array
	{
		return [
			APIVersioningServiceProvider::class,
			GraphQLPlatformServiceProvider::class,
		];
	}

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app['config']->set('app.debug', true);
	}

	protected function defineDatabaseMigrations(): void
	{
		$this->loadMigrationsFrom(
			package_path('resources/database/migrations')
		);
	}
}
