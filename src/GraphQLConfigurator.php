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
use TenantCloud\APIVersioning\Version\LatestVersion;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Server\Http\DefaultRequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Server\Http\RequestSchemaProvider;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\CachePersistedQueryLoader;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\NotSupportedPersistedQueryLoader;
use TheCodingMachine\GraphQLite\Utils\Cloneable;

/**
 * @phpstan-import-type PersistedQueryLoader from ServerConfig
 */
final class GraphQLConfigurator
{
	use Cloneable;

	/**
	 * @param PersistedQueryLoader|null                                                          $persistedQueryLoader
	 * @param list<callable(Router, UrlGenerator): Route>                                        $routes
	 * @param array<string, SchemaConfigurator|callable(SchemaConfigurator): SchemaConfigurator> $schemas
	 * @param list<ValidationRule>                                                               $validationRules
	 */
	public function __construct(
		public readonly mixed $persistedQueryLoader = new NotSupportedPersistedQueryLoader(),
		public readonly array $routes = [],
		public readonly array $schemas = [],
		public readonly array $validationRules = [],
		public readonly bool $devMode = false,
		public readonly ?string $subscriptionTransportName = null,
	) {}

	public function useAutomaticPersistedQueries(CacheInterface $cache, DateInterval $ttl = new CarbonInterval('P1D')): self
	{
		return $this->with(
			persistedQueryLoader: new CachePersistedQueryLoader($cache, $ttl),
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

	public function devMode(bool $value = true): self
	{
		return $this->with(devMode: $value);
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
		?string $graphQLEndpoint = null,
		?callable $callback = null,
	): self {
		$graphQLEndpoint ??= GraphQLPlatform::namespaced('graphql');

		return $this->addRoute(fn (Router $router, UrlGenerator $urlGenerator) => with(
			$router->view(
				$endpoint,
				GraphQLPlatform::namespaced('explore'),
				[
					'endpoint' => $router->has($graphQLEndpoint) ?
						$urlGenerator->route($graphQLEndpoint) :
						$urlGenerator->to($graphQLEndpoint),
					'latestVersion' => (string) new LatestVersion(),
				],
			),
			$callback,
		));
	}

	/**
	 * @param class-string<RequestSchemaProvider> $schemaProvider
	 */
	public function addGraphQLRoute(
		string $endpoint = '/graphql',
		?string $name = null,
		?callable $callback = null,
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

	public function addSchema(string $name, callable|SchemaConfigurator|null $configurator = null): self
	{
		return $this->with(
			schemas: [
				...$this->schemas,
				$name => $configurator,
			],
		);
	}

	public function useSubscriptionTransport(?string $transportName): self
	{
		return $this->with(
			subscriptionTransportName: $transportName,
		);
	}

	private function addRoute(callable $route): self
	{
		return $this->with(
			routes: [...$this->routes, $route],
		);
	}
}
