<?php

namespace TenantCloud\GraphQLPlatform;

use Carbon\CarbonInterval;
use DateInterval;
use GraphQL\Server\ServerConfig;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\ValidationRule;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PackageVersions\Versions;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TenantCloud\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Http\DefaultRequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Http\RequestSchemaProvider;
use TenantCloud\GraphQLPlatform\PersistedQuery\CachePersistedQueryLoader;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsFieldMiddleware;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryProviderFactoryInterface;
use TheCodingMachine\GraphQLite\QueryProviderInterface;
use TheCodingMachine\GraphQLite\Utils\Cloneable;

final class GraphQLConfigurator
{
	use Cloneable;

	/**
	 * @phpstan-import-type PersistedQueryLoader from ServerConfig
	 *
	 * @param PersistedQueryLoader|null $persistedQueryLoader
	 * @param array<callable(Router, UrlGenerator): Route> $routes
	 * @param array<string, SchemaConfigurator|callable(SchemaConfigurator): SchemaConfigurator> $schemas
	 * @param ValidationRule[] $validationRules
	 */
	public function __construct(
		public readonly mixed $persistedQueryLoader = null,
		public readonly array $routes = [],
		public readonly array $schemas = [],
		public readonly array $validationRules = [],
	)
	{
	}

	public function useAutomaticPersistedQueries(CacheInterface $cache, ?DateInterval $ttl = null): self
	{
		return $this->with(
			persistedQueryLoader: new CachePersistedQueryLoader($cache, $ttl ?? CarbonInterval::day()),
		);
	}

	public function limitQueryComplexity(int $maxComplexity = 500): self
	{
		return $this->addValidationRule(new QueryComplexity($maxComplexity));
	}

	public function limitQueryDepth(int $maxDepth = 7): self
	{
		return $this->addValidationRule(new QueryDepth($maxDepth));
	}

	public function disableIntrospection(): self
	{
		return $this->addValidationRule(new DisableIntrospection(DisableIntrospection::ENABLED));
	}

	public function addValidationRule(ValidationRule $rule): self
	{
		return $this->with(validationRules: [
			...$this->validationRules,
			$rule
		]);
	}

	public function addExploreRoute(
		string $endpoint = '/graphql/explore',
		string $graphQLEndpoint = null,
		callable $callback = null,
	): self
	{
		$graphQLEndpoint ??= GraphQLPlatform::namespaced('graphql');

		return $this->addRoute(fn (Router $router, UrlGenerator $urlGenerator) => with(
			$router->view(
				$endpoint,
				GraphQLPlatform::namespaced('explore'),
				['endpoint' => match(true) {
					$router->has($graphQLEndpoint) => $urlGenerator->route($graphQLEndpoint),
					default => $urlGenerator->to($graphQLEndpoint),
				}],
			),
			$callback,
		));
	}

	/**
	 * @param class-string<RequestSchemaProvider> $schemaProvider
	 */
	public function addGraphQLRoute(
		string $endpoint = '/graphql',
		string $name = null,
		callable $callback = null,
		string $schemaProvider = DefaultRequestSchemaProvider::class,
	): self {
		$name ??= GraphQLPlatform::namespaced('graphql');

		return $this->addRoute(fn (Router $router) => with(
			$router->name($name)
				->match(
					['GET', 'POST'],
					$endpoint,
					GraphQLController::class,
				)
				->defaults('schemaProvider', $schemaProvider),
			$callback
		));
	}

	public function addDefaultSchema(callable|SchemaConfigurator $configurator = null): self
	{
		return $this->addSchema(SchemaRegistry::DEFAULT, $configurator);
	}

	public function addSchema(string $name, callable|SchemaConfigurator $configurator = null): self
	{
		return $this->with(
			schemas: [
				...$this->schemas,
				$name => $configurator,
			],
		);
	}

	private function addRoute(callable $route): self
	{
		return $this->with(
			routes: [...$this->routes, $route],
		);
	}
}
