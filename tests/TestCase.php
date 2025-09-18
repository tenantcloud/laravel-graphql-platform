<?php

namespace Tests;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Server\ServerConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TenantCloud\APIVersioning\APIVersioningServiceProvider;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;
use TenantCloud\GraphQLPlatform\Testing\FakeSubscriptionTransport;
use Tests\Fixtures\Valid\Models\Eloquent\User;
use Tests\Fixtures\Valid\Policies\UserPolicy;
use Tests\Fixtures\Valid\TypeMappers\AnyRootTypeMapper;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use Throwable;

use function Orchestra\Testbench\package_path;

#[WithConfig('app.debug', true)]
#[WithConfig('auth.providers.users.model', User::class)]
abstract class TestCase extends BaseTestCase
{
	use LazilyRefreshDatabase;
	use WithFaker;

	protected function setUp(): void
	{
		parent::setUp();

		$this->afterApplicationCreated(function () {
			UserPolicy::$calledTimes = 0;

			$this->app->extend(ServerConfig::class, static function (ServerConfig $config, Application $app) {
				$formatter = FormattedError::prepareFormatter(
					WebonyxErrorHandler::errorFormatter(...),
					DebugFlag::RETHROW_UNSAFE_EXCEPTIONS | DebugFlag::INCLUDE_TRACE
				);

				$config->setErrorFormatter(function (Throwable $error) use ($formatter) {
					if ($error instanceof Error && $error->getPrevious() instanceof AuthorizationException) {
						$error = new GraphQLException($error->getPrevious()->getMessage());
					}

					return $formatter($error);
				});

				return $config;
			});

			$this->app->extend(
				SchemaConfigurator::class,
				fn (SchemaConfigurator $configurator) => $configurator
					->usingComposerClassFinder(['Tests\\Fixtures\\Valid'])
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
					->useSubscriptionTransport(FakeSubscriptionTransport::TYPE)
			);

			$this->app->extend(SubscriptionTransportManager::class, function (SubscriptionTransportManager $subscriptionTransportManager) {
				$subscriptionTransportManager->extend(FakeSubscriptionTransport::TYPE, fn () => new FakeSubscriptionTransport());

				return $subscriptionTransportManager;
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

	protected function defineDatabaseMigrations(): void
	{
		$this->loadMigrationsFrom(
			package_path('resources/database/migrations')
		);
		$this->loadMigrationsFrom(
			package_path('tests/Fixtures/Database/migrations')
		);
	}
}
