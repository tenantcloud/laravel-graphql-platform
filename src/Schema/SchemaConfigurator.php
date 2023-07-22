<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use PackageVersions\Versions;
use TenantCloud\APIVersioning\Version\Version;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryProviderFactoryInterface;
use TheCodingMachine\GraphQLite\QueryProviderInterface;
use TheCodingMachine\GraphQLite\Utils\Cloneable;

final class SchemaConfigurator
{
	use Cloneable;

	public readonly string $cacheNamespace;

	/**
	 * @param string[]                         $controllerNamespaces
	 * @param string[]                         $typeNamespaces
	 * @param QueryProviderInterface[]         $queryProviders
	 * @param QueryProviderFactoryInterface[]  $queryProviderFactories
	 * @param RootTypeMapperFactoryInterface[] $rootTypeMapperFactories
	 * @param TypeMapperInterface[]            $typeMappers
	 * @param TypeMapperFactoryInterface[]     $typeMapperFactories
	 * @param ParameterMiddlewareInterface[]   $parameterMiddlewares
	 * @param FieldMiddlewareInterface[]       $fieldMiddlewares
	 * @param InputFieldMiddlewareInterface[]  $inputFieldMiddlewares
	 */
	public function __construct(
		public readonly array $controllerNamespaces = [],
		public readonly array $typeNamespaces = [],
		public readonly array $queryProviders = [],
		public readonly array $queryProviderFactories = [],
		public readonly array $rootTypeMapperFactories = [],
		public readonly array $typeMappers = [],
		public readonly array $typeMapperFactories = [],
		public readonly array $parameterMiddlewares = [],
		public readonly int|null $globTTL = 2,
		public readonly array $fieldMiddlewares = [],
		public readonly array $inputFieldMiddlewares = [],
		public readonly string|Version|null $forVersion = null,
	) {
		$this->cacheNamespace = mb_substr(md5(Versions::getVersion('thecodingmachine/graphqlite')), 0, 8);
	}

	/**
	 * Sets the time to live time of the cache for annotations in files.
	 * By default this is set to 2 seconds which is ok for development environments.
	 * Set this to "null" (i.e. infinity) for production environments.
	 */
	public function globTTL(int|null $globTTL): self
	{
		return $this->with(globTTL: $globTTL);
	}

	/**
	 * Sets GraphQLite in "prod" mode (cache settings optimized for best performance).
	 */
	public function prodMode(): self
	{
		return $this->globTTL(null);
	}

	/**
	 * Sets GraphQLite in "dev" mode (this is the default mode: cache settings optimized for best developer experience).
	 */
	public function devMode(): self
	{
		return $this->globTTL(2);
	}

	public function forVersion(string|Version $version): self
	{
		return $this->with(forVersion: $version);
	}

	/**
	 * Registers a namespace that can contain GraphQL controllers.
	 */
	public function addControllerNamespace(string $namespace): self
	{
		return $this->with(controllerNamespaces: [
			...$this->controllerNamespaces,
			$namespace,
		]);
	}

	/**
	 * Registers a namespace that can contain GraphQL types.
	 */
	public function addTypeNamespace(string $namespace): self
	{
		return $this->with(typeNamespaces: [
			...$this->typeNamespaces,
			$namespace,
		]);
	}

	/**
	 * Registers a query provider.
	 */
	public function addQueryProvider(QueryProviderInterface $queryProvider): self
	{
		return $this->with(queryProviders: [
			...$this->queryProviders,
			$queryProvider,
		]);
	}

	/**
	 * Registers a query provider factory.
	 */
	public function addQueryProviderFactory(QueryProviderFactoryInterface $queryProviderFactory): self
	{
		return $this->with(queryProviderFactories: [
			...$this->queryProviderFactories,
			$queryProviderFactory,
		]);
	}

	/**
	 * Registers a root type mapper factory.
	 */
	public function addRootTypeMapperFactory(RootTypeMapperFactoryInterface $rootTypeMapperFactory): self
	{
		return $this->with(rootTypeMapperFactories: [
			...$this->rootTypeMapperFactories,
			$rootTypeMapperFactory,
		]);
	}

	/**
	 * Registers a type mapper.
	 */
	public function addTypeMapper(TypeMapperInterface $typeMapper): self
	{
		return $this->with(typeMappers: [
			...$this->typeMappers,
			$typeMapper,
		]);
	}

	/**
	 * Registers a type mapper factory.
	 */
	public function addTypeMapperFactory(TypeMapperFactoryInterface $typeMapperFactory): self
	{
		return $this->with(typeMapperFactories: [
			...$this->typeMapperFactories,
			$typeMapperFactory,
		]);
	}

	/**
	 * Registers a parameter middleware.
	 */
	public function addParameterMiddleware(ParameterMiddlewareInterface $parameterMiddleware): self
	{
		return $this->with(parameterMiddlewares: [
			...$this->parameterMiddlewares,
			$parameterMiddleware,
		]);
	}

	/**
	 * Registers a field middleware (used to parse custom annotations that modify the GraphQLite behaviour in Fields/Queries/Mutations.
	 */
	public function addFieldMiddleware(FieldMiddlewareInterface $fieldMiddleware): self
	{
		return $this->with(fieldMiddlewares: [
			...$this->fieldMiddlewares,
			$fieldMiddleware,
		]);
	}

	/**
	 * Registers a input field middleware (used to parse custom annotations that modify the GraphQLite behaviour in Fields/Queries/Mutations.
	 */
	public function addInputFieldMiddleware(InputFieldMiddlewareInterface $inputFieldMiddleware): self
	{
		return $this->with(inputFieldMiddlewares: [
			...$this->inputFieldMiddlewares,
			$inputFieldMiddleware,
		]);
	}
}
