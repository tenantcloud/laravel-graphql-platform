<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use Mouf\Composer\ClassNameMapper;
use Psr\Container\ContainerInterface;
use TenantCloud\APIVersioning\Constraint\ConstraintChecker;
use TenantCloud\APIVersioning\Version\Version;
use TenantCloud\APIVersioning\Version\VersionParser;
use TenantCloud\GraphQLPlatform\Connection\ConnectionFieldMiddleware;
use TenantCloud\GraphQLPlatform\Connection\ConnectionTypeMapper;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ModelIDTypeMapper;
use TenantCloud\GraphQLPlatform\Laravel\LaravelContainerHandle;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LaravelPaginationFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LaravelPaginationTypeMapper;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueTypeMapper;
use TenantCloud\GraphQLPlatform\Scalars\Carbon\CarbonRootTypeMapper;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsFieldMiddleware;
use TheCodingMachine\GraphQLite\AggregateQueryProvider;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\FieldsBuilder;
use TheCodingMachine\GraphQLite\GlobControllerQueryProvider;
use TheCodingMachine\GraphQLite\InputTypeGenerator;
use TheCodingMachine\GraphQLite\InputTypeUtils;
use TheCodingMachine\GraphQLite\Mappers\CompositeTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\GlobTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewarePipe;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\BaseTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\CompoundTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\EnumTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\FinalRootTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\IteratorTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\LastDelegatingTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\NullableTypeMapperAdapter;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\VoidTypeMapper;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewarePipe;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewarePipe;
use TheCodingMachine\GraphQLite\NamingStrategy;
use TheCodingMachine\GraphQLite\Reflection\CachedDocBlockFactory;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\TypeGenerator;
use TheCodingMachine\GraphQLite\TypeRegistry;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;
use TheCodingMachine\GraphQLite\Types\TypeResolver;
use TheCodingMachine\GraphQLite\Utils\Namespaces\NamespaceFactory;

class SchemaFactory
{
	public function __construct(
		private readonly ContainerInterface $container,
	) {}

	public function create(SchemaConfigurator $configurator): Schema
	{
		$namespacedCache = $this->container->get('graphqlite.namespaced_cache');
		$typeNamespaces = array_map(
			fn (string $namespace) => $this->container
				->get(NamespaceFactory::class)
				->createNamespace($namespace),
			$configurator->typeNamespaces,
		);
		$typeResolver = new TypeResolver();
		$typeRegistry = new TypeRegistry();

		$compositeTypeMapper = new CompositeTypeMapper();
		$recursiveTypeMapper = new RecursiveTypeMapper(
			$compositeTypeMapper,
			$this->container->get(NamingStrategy::class),
			$namespacedCache,
			$typeRegistry,
			$this->container->get(AnnotationReader::class)
		);

		$lastTopRootTypeMapper = new LastDelegatingTypeMapper();
		$topRootTypeMapper = new NullableTypeMapperAdapter($lastTopRootTypeMapper);
		$topRootTypeMapper = new MissingValueTypeMapper($topRootTypeMapper);
		$topRootTypeMapper = new VoidTypeMapper($topRootTypeMapper);

		$errorRootTypeMapper = new FinalRootTypeMapper($recursiveTypeMapper);

		$rootTypeMapper = new BaseTypeMapper($errorRootTypeMapper, $recursiveTypeMapper, $topRootTypeMapper);
		$rootTypeMapper = new EnumTypeMapper(
			$rootTypeMapper,
			$this->container->get(AnnotationReader::class),
			$this->container->get('graphqlite.symfony_cache'),
			$typeNamespaces
		);
		$rootTypeMapper = new ModelIDTypeMapper($rootTypeMapper);
		$rootTypeMapper = new CarbonRootTypeMapper($rootTypeMapper);

		if ($configurator->rootTypeMapperFactories) {
			$rootSchemaFactoryContext = new RootTypeMapperFactoryContext(
				$this->container->get(AnnotationReader::class),
				$typeResolver,
				$this->container->get(NamingStrategy::class),
				$typeRegistry,
				$recursiveTypeMapper,
				$this->container,
				$namespacedCache,
				$typeNamespaces,
				$configurator->globTTL,
			);

			foreach (array_reverse($configurator->rootTypeMapperFactories) as $rootTypeMapperFactory) {
				$rootTypeMapper = $rootTypeMapperFactory->create($rootTypeMapper, $rootSchemaFactoryContext);
			}
		}

		$rootTypeMapper = new CompoundTypeMapper(
			$rootTypeMapper,
			$topRootTypeMapper,
			$this->container->get(NamingStrategy::class),
			$typeRegistry,
			$recursiveTypeMapper
		);
		$rootTypeMapper = new IteratorTypeMapper($rootTypeMapper, $topRootTypeMapper);
		$rootTypeMapper = $connectionTypeMapper = new ConnectionTypeMapper(
			$rootTypeMapper,
			$topRootTypeMapper,
			$this->container->get(AnnotationReader::class),
		);
		$rootTypeMapper = new LaravelPaginationTypeMapper($rootTypeMapper);

		$lastTopRootTypeMapper->setNext($rootTypeMapper);

		$fieldMiddlewarePipe = new FieldMiddlewarePipe();
		$inputFieldMiddlewarePipe = new InputFieldMiddlewarePipe();
		$parameterMiddlewarePipe = new ParameterMiddlewarePipe();

		$fieldsBuilder = new FieldsBuilder(
			$this->container->get(AnnotationReader::class),
			$recursiveTypeMapper,
			$this->container->get(ArgumentResolver::class),
			$typeResolver,
			$this->container->get(CachedDocBlockFactory::class),
			$this->container->get(NamingStrategy::class),
			$topRootTypeMapper,
			$parameterMiddlewarePipe,
			$fieldMiddlewarePipe,
			$inputFieldMiddlewarePipe,
		);

		if ($configurator->forVersion) {
			$fieldMiddlewarePipe->pipe(new ForVersionsFieldMiddleware(
				$configurator->forVersion instanceof Version ?
					$configurator->forVersion :
					$this->container->get(VersionParser::class)->parse($configurator->forVersion),
				$this->container->get(ConstraintChecker::class),
			));
		}

		$fieldMiddlewarePipe->pipe(new ConnectionFieldMiddleware(
			$connectionTypeMapper,
			$this->container->get(CachedDocBlockFactory::class),
			$this->container->get(ArgumentResolver::class)
		));
		$fieldMiddlewarePipe->pipe(new LaravelPaginationFieldMiddleware($connectionTypeMapper));

		foreach ($configurator->fieldMiddlewares as $fieldMiddleware) {
			$fieldMiddlewarePipe->pipe($fieldMiddleware);
		}

		foreach ($configurator->inputFieldMiddlewares as $inputFieldMiddleware) {
			$inputFieldMiddlewarePipe->pipe($inputFieldMiddleware);
		}

		foreach ($configurator->parameterMiddlewares as $parameterMiddleware) {
			$parameterMiddlewarePipe->pipe($parameterMiddleware);
		}

		$typeGenerator = new TypeGenerator(
			$this->container->get(AnnotationReader::class),
			$this->container->get(NamingStrategy::class),
			$typeRegistry,
			$this->container->get(LaravelContainerHandle::class),
			$recursiveTypeMapper,
			$fieldsBuilder
		);
		$inputTypeGenerator = new InputTypeGenerator(
			$this->container->get(InputTypeUtils::class),
			$fieldsBuilder,
			$this->container->has(InputTypeValidatorInterface::class) ?
				$this->container->get(InputTypeValidatorInterface::class) :
				null
		);

		foreach ($typeNamespaces as $ns) {
			$compositeTypeMapper->addTypeMapper(new GlobTypeMapper(
				$ns,
				$typeGenerator,
				$inputTypeGenerator,
				$this->container->get(InputTypeUtils::class),
				$this->container,
				$this->container->get(AnnotationReader::class),
				$this->container->get(NamingStrategy::class),
				$recursiveTypeMapper,
				$namespacedCache,
				null,
			));
		}

		$queryProviders = [];

		foreach ($configurator->controllerNamespaces as $controllerNamespace) {
			$queryProviders[] = new GlobControllerQueryProvider(
				$controllerNamespace,
				$fieldsBuilder,
				$this->container->get(LaravelContainerHandle::class),
				$this->container->get(AnnotationReader::class),
				$namespacedCache,
				$this->container->get(ClassNameMapper::class),
				null,
			);
		}

		$aggregateQueryProvider = new AggregateQueryProvider($queryProviders);

		return new Schema(
			$aggregateQueryProvider,
			$recursiveTypeMapper,
			$typeResolver,
			$topRootTypeMapper
		);
	}
}
