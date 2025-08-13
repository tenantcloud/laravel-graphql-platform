<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TenantCloud\APIVersioning\Constraint\ConstraintChecker;
use TenantCloud\APIVersioning\Version\Version;
use TenantCloud\APIVersioning\Version\VersionParser;
use TenantCloud\GraphQLPlatform\Connection\ConnectionFieldMiddleware;
use TenantCloud\GraphQLPlatform\Connection\ConnectionTypeMapper;
use TenantCloud\GraphQLPlatform\Laravel\Container\LaravelContainerHandle;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelIDTypeMapper;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation\RelationRootTypeMapper;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueTypeMapper;
use TenantCloud\GraphQLPlatform\Scalars\ScalarsRootTypeMapper;
use TenantCloud\GraphQLPlatform\Utility\TrimDescriptionsFieldMiddleware;
use TenantCloud\GraphQLPlatform\Utility\TrimDescriptionsInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptionsParameterMiddleware;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyMapping;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyMappingInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyPathMapper;
use TenantCloud\GraphQLPlatform\Validation\ValidationParameterMiddleware;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsFieldMiddleware;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsInputFieldMiddleware;
use TheCodingMachine\GraphQLite\AggregateQueryProvider;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Cache\ClassBoundCache;
use TheCodingMachine\GraphQLite\Discovery\Cache\ClassFinderComputedCache;
use TheCodingMachine\GraphQLite\FieldsBuilder;
use TheCodingMachine\GraphQLite\GlobControllerQueryProvider;
use TheCodingMachine\GraphQLite\InputTypeGenerator;
use TheCodingMachine\GraphQLite\InputTypeUtils;
use TheCodingMachine\GraphQLite\Mappers\ClassFinderTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\CompositeTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewarePipe;
use TheCodingMachine\GraphQLite\Mappers\Parameters\PrefetchParameterMiddleware;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\BaseTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\Root\ClosureTypeMapper;
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
use TheCodingMachine\GraphQLite\ParameterizedCallableResolver;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\DocBlockFactory;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\TypeGenerator;
use TheCodingMachine\GraphQLite\TypeRegistry;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\TypeResolver;
use Webmozart\Assert\Assert;

class SchemaFactory
{
	public function __construct(
		private readonly ContainerInterface $container,
	) {}

	public function create(SchemaConfigurator $configurator): Schema
	{
		Assert::notNull($configurator->classFinder, 'You must provide a ClassFinder to find the classes.');

		$psr16Cache = $this->container->get('graphqlite.psr16_cache');
		$typeResolver = new TypeResolver();
		$typeRegistry = new TypeRegistry();

		$compositeTypeMapper = new CompositeTypeMapper();
		$recursiveTypeMapper = new RecursiveTypeMapper(
			$compositeTypeMapper,
			$this->container->get(NamingStrategy::class),
			$psr16Cache,
			$typeRegistry,
			$this->container->get(AnnotationReader::class)
		);

		$lastTopRootTypeMapper = new LastDelegatingTypeMapper();
		$topRootTypeMapper = new NullableTypeMapperAdapter($lastTopRootTypeMapper);
		$topRootTypeMapper = new MissingValueTypeMapper($topRootTypeMapper);
		$topRootTypeMapper = new VoidTypeMapper($topRootTypeMapper);
		$topRootTypeMapper = new ClosureTypeMapper($topRootTypeMapper, $lastTopRootTypeMapper);
		$topRootTypeMapper = new RelationRootTypeMapper($topRootTypeMapper);

		$errorRootTypeMapper = new FinalRootTypeMapper($recursiveTypeMapper);

		$rootTypeMapper = new BaseTypeMapper($errorRootTypeMapper, $recursiveTypeMapper, $topRootTypeMapper);
		$rootTypeMapper = new EnumTypeMapper(
			$rootTypeMapper,
			$this->container->get(AnnotationReader::class),
			$this->container->get(DocBlockFactory::class),
			$configurator->classFinder,
			$this->container->get(ClassFinderComputedCache::class),
		);
		$rootTypeMapper = new ModelIDTypeMapper($rootTypeMapper);
		$rootTypeMapper = new ScalarsRootTypeMapper($rootTypeMapper);

		if ($configurator->rootTypeMapperFactories) {
			$rootSchemaFactoryContext = new RootTypeMapperFactoryContext(
				$this->container->get(AnnotationReader::class),
				$typeResolver,
				$this->container->get(NamingStrategy::class),
				$typeRegistry,
				$recursiveTypeMapper,
				$this->container,
				$psr16Cache,
				$configurator->classFinder,
				$this->container->get(ClassFinderComputedCache::class),
				$this->container->get(ClassBoundCache::class),
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
			$configurator->defaultConnectionsLimit,
		);

		$lastTopRootTypeMapper->setNext($rootTypeMapper);

		$propertyMapping = new PropertyMapping();
		$propertyPathMapper = new PropertyPathMapper(
			$propertyMapping,
			PropertyAccess::createPropertyAccessor(),
		);
		$validationExceptions = new ValidationExceptions($propertyPathMapper);

		$fieldMiddlewarePipe = new FieldMiddlewarePipe();
		$inputFieldMiddlewarePipe = new InputFieldMiddlewarePipe();
		$parameterMiddlewarePipe = new ParameterMiddlewarePipe();

		$fieldsBuilder = new FieldsBuilder(
			$this->container->get(AnnotationReader::class),
			$recursiveTypeMapper,
			$this->container->get(ArgumentResolver::class),
			$typeResolver,
			$this->container->get(DocBlockFactory::class),
			$this->container->get(NamingStrategy::class),
			$topRootTypeMapper,
			$parameterMiddlewarePipe,
			$fieldMiddlewarePipe,
			$inputFieldMiddlewarePipe,
		);

		if ($configurator->forVersion) {
			$version = $configurator->forVersion instanceof Version ?
				$configurator->forVersion :
				$this->container->get(VersionParser::class)->parse($configurator->forVersion);

			$fieldMiddlewarePipe->pipe(new ForVersionsFieldMiddleware($version, $this->container->get(ConstraintChecker::class)));
			$inputFieldMiddlewarePipe->pipe(new ForVersionsInputFieldMiddleware($version, $this->container->get(ConstraintChecker::class)));
		}

		$fieldMiddlewarePipe->pipe(new ConnectionFieldMiddleware(
			$connectionTypeMapper,
			$this->container->get(DocBlockFactory::class),
			$this->container->get(ArgumentResolver::class)
		));

		$inputFieldMiddlewarePipe->pipe(new PropertyMappingInputFieldMiddleware($propertyMapping));

		$parameterMiddlewarePipe->pipe(new ValidationParameterMiddleware(
			$this->container->get(ValidatorInterface::class),
			$validationExceptions,
		));
		$parameterMiddlewarePipe->pipe(new PrefetchParameterMiddleware(
			new ParameterizedCallableResolver($fieldsBuilder, $this->container)
		));
		$parameterMiddlewarePipe->pipe(new ValidationExceptionsParameterMiddleware(
			$validationExceptions,
		));

		foreach ($configurator->fieldMiddlewares as $fieldMiddleware) {
			$fieldMiddlewarePipe->pipe($fieldMiddleware);
		}

		foreach ($configurator->inputFieldMiddlewares as $inputFieldMiddleware) {
			$inputFieldMiddlewarePipe->pipe($inputFieldMiddleware);
		}

		foreach ($configurator->parameterMiddlewares as $parameterMiddleware) {
			$parameterMiddlewarePipe->pipe($parameterMiddleware);
		}

		$fieldMiddlewarePipe->pipe(new TrimDescriptionsFieldMiddleware());
		$inputFieldMiddlewarePipe->pipe(new TrimDescriptionsInputFieldMiddleware());

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
		);

		$compositeTypeMapper->addTypeMapper(new ClassFinderTypeMapper(
			$configurator->classFinder,
			$typeGenerator,
			$inputTypeGenerator,
			$this->container->get(InputTypeUtils::class),
			$this->container,
			$this->container->get(AnnotationReader::class),
			$this->container->get(NamingStrategy::class),
			$recursiveTypeMapper,
			$this->container->get(ClassFinderComputedCache::class),
		));

		$queryProviders = [
			new GlobControllerQueryProvider(
				$fieldsBuilder,
				$this->container->get(LaravelContainerHandle::class),
				$this->container->get(AnnotationReader::class),
				$configurator->classFinder,
				$this->container->get(ClassFinderComputedCache::class),
			),
		];

		$aggregateQueryProvider = new AggregateQueryProvider($queryProviders);

		return new Schema(
			$aggregateQueryProvider,
			$recursiveTypeMapper,
			$typeResolver,
			$topRootTypeMapper
		);
	}
}
