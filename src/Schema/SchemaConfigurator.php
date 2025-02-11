<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use TenantCloud\APIVersioning\Version\Version;
use TenantCloud\GraphQLPlatform\Discovery\Composer\ComposerClassFinder;
use TheCodingMachine\GraphQLite\Discovery\ClassFinder;
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

	/**
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
		public readonly ?ClassFinder $classFinder = null,
		public readonly array $queryProviders = [],
		public readonly array $queryProviderFactories = [],
		public readonly array $rootTypeMapperFactories = [],
		public readonly array $typeMappers = [],
		public readonly array $typeMapperFactories = [],
		public readonly array $parameterMiddlewares = [],
		public readonly array $fieldMiddlewares = [],
		public readonly array $inputFieldMiddlewares = [],
		public readonly string|Version|null $forVersion = null,
		public readonly int $defaultConnectionsLimit = 100,
	) {}

	public function usingClassFinder(ClassFinder $classFinder): self
	{
		return $this->with(classFinder: $classFinder);
	}

	/**
	 * @param list<string>                $namespaces
	 * @param callable(string): bool|null $pathFilter
	 */
	public function usingComposerClassFinder(array $namespaces, callable $pathFilter = null): self
	{
		return $this->usingClassFinder(
			ComposerClassFinder::default($namespaces, $pathFilter ? [$pathFilter] : []),
		);
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
	public function addParameterMiddleware(ParameterMiddlewareInterface $parameterMiddleware, bool $prepend = false): self
	{
		return $this->with(parameterMiddlewares: [
			...($prepend ? [$parameterMiddleware] : []),
			...$this->parameterMiddlewares,
			...(!$prepend ? [$parameterMiddleware] : []),
		]);
	}

	/**
	 * Registers a field middleware (used to parse custom annotations that modify the GraphQLite behaviour in Fields/Queries/Mutations).
	 */
	public function addFieldMiddleware(FieldMiddlewareInterface $fieldMiddleware, bool $prepend = false): self
	{
		return $this->with(fieldMiddlewares: [
			...($prepend ? [$fieldMiddleware] : []),
			...$this->fieldMiddlewares,
			...(!$prepend ? [$fieldMiddleware] : []),
		]);
	}

	/**
	 * Registers a input field middleware (used to parse custom annotations that modify the GraphQLite behaviour in Fields/Queries/Mutations).
	 */
	public function addInputFieldMiddleware(InputFieldMiddlewareInterface $inputFieldMiddleware, bool $prepend = false): self
	{
		return $this->with(inputFieldMiddlewares: [
			...($prepend ? [$inputFieldMiddleware] : []),
			...$this->inputFieldMiddlewares,
			...(!$prepend ? [$inputFieldMiddleware] : []),
		]);
	}

	public function forVersion(string|Version $version): self
	{
		return $this->with(forVersion: $version);
	}

	public function defaultConnectionsLimit(int $limit): self
	{
		return $this->with(defaultConnectionsLimit: $limit);
	}
}
