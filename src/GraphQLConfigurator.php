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
use Psr\SimpleCache\CacheInterface;
use TenantCloud\GraphQLPlatform\Http\DefaultRequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Http\RequestSchemaProvider;
use TenantCloud\GraphQLPlatform\PersistedQuery\CachePersistedQueryLoader;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TheCodingMachine\GraphQLite\Utils\Cloneable;

/**
 * @phpstan-import-type PersistedQueryLoader from ServerConfig
 */
final class GraphQLConfigurator
{
	use Cloneable;

	/**
	 * @param PersistedQueryLoader|null                                                          $persistedQueryLoader
	 * @param array<callable(Router, UrlGenerator): Route>                                       $routes
	 * @param array<string, SchemaConfigurator|callable(SchemaConfigurator): SchemaConfigurator> $schemas
	 * @param ValidationRule[]                                                                   $validationRules
	 */
	public function __construct(
		public readonly mixed $persistedQueryLoader = null,
		public readonly array $routes = [],
		public readonly array $schemas = [],
		public readonly array $validationRules = [],
	) {}

	public function useAutomaticPersistedQueries(CacheInterface $cache, DateInterval $ttl = null): self
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
			$rule,
		]);
	}

	public function addExploreRoute(
		string $endpoint = '/graphql/explore',
		string $graphQLEndpoint = null,
		callable $callback = null,
	): self {
		$graphQLEndpoint ??= GraphQLPlatform::namespaced('graphql');

		return $this->addRoute(fn (Router $router, UrlGenerator $urlGenerator) => with(
			$router->view(
				$endpoint,
				GraphQLPlatform::namespaced('explore'),
				['endpoint' => match (true) {
					$router->has($graphQLEndpoint) => $urlGenerator->route($graphQLEndpoint),
					default                        => $urlGenerator->to($graphQLEndpoint),
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
