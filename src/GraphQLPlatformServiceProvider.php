<?php

namespace TenantCloud\GraphQLPlatform;

use GraphQL\Error\DebugFlag;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema as WebonyxSchema;
use GraphQL\Validator\DocumentValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use PackageVersions\Versions;
use phpDocumentor\Reflection\Types\ContextFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Command\DebugCommand;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use TenantCloud\GraphQLPlatform\Laravel\Auth\LaravelAuthenticationService;
use TenantCloud\GraphQLPlatform\Laravel\Auth\LaravelAuthorizationService;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ModelIDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ModelIDParameterMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation\PreventLazyLoadingFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\TransactionalFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\LaravelContainerHandle;
use TenantCloud\GraphQLPlatform\Laravel\Octane\GiveNewApplicationInstanceToContainerHandle;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\QueryBuilderConnectable;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Scalars\ID\IDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Schema\PrintCommand;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaFactory;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Selection\InjectSelectionParameterMiddleware;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLResponseHttpCodeDecider;
use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\ReflectionConstraintDescriptionProvider;
use TenantCloud\GraphQLPlatform\Validation\DescribeValidationInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Validation\LaravelCompositeTranslatorAdapter;
use TenantCloud\GraphQLPlatform\Validation\SkipMissingValueConstraintValidatorFactory;
use TenantCloud\GraphQLPlatform\Validation\SymfonyInputTypeValidator;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Cache\ClassBoundCache;
use TheCodingMachine\GraphQLite\Cache\FileModificationClassBoundCache;
use TheCodingMachine\GraphQLite\Cache\HardClassBoundCache;
use TheCodingMachine\GraphQLite\Discovery\Cache\ClassFinderComputedCache;
use TheCodingMachine\GraphQLite\Discovery\Cache\FileModificationClassFinderComputedCache;
use TheCodingMachine\GraphQLite\Discovery\Cache\HardClassFinderComputedCache;
use TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use TheCodingMachine\GraphQLite\InputTypeUtils;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ContainerParameterHandler;
use TheCodingMachine\GraphQLite\Mappers\Parameters\InjectUserParameterHandler;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ResolveInfoParameterHandler;
use TheCodingMachine\GraphQLite\Middlewares\AuthorizationFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\AuthorizationInputFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\CostFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\SecurityFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\SecurityInputFieldMiddleware;
use TheCodingMachine\GraphQLite\NamingStrategy;
use TheCodingMachine\GraphQLite\NamingStrategyInterface;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\CachedDocBlockContextFactory;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\CachedDocBlockFactory;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\DocBlockContextFactory;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\DocBlockFactory;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\PhpDocumentorDocBlockContextFactory;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\PhpDocumentorDocBlockFactory;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;
use TheCodingMachine\GraphQLite\Security\SecurityExpressionLanguageProvider;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;
use Webmozart\Assert\Assert;

class GraphQLPlatformServiceProvider extends ServiceProvider
{
	public const CONTAINER_HANDLE = 'graphql-platform.container_handle';

	public function register(): void
	{
		$this->registerContainer();
		$this->registerCache();
		$this->registerUtils();
		$this->registerSchema();
		$this->registerHttp();
		$this->registerConnections();
		$this->registerValidation();
		$this->registerAuthentication();
	}

	public function boot(
		GraphQLConfigurator $graphQLConfigurator,
		Factory $viewFactory,
		Router $router,
		UrlGenerator $urlGenerator,
		SchemaRegistry $schemaRegistry,
		Dispatcher $events,
	): void {
		if ($this->app->runningInConsole()) {
			$this->commands([
				DebugCommand::class,
				PrintCommand::class,
			]);
		}

		if (class_exists(\Laravel\Octane\Octane::class)) {
			$events->listen(\Laravel\Octane\Events\RequestReceived::class, GiveNewApplicationInstanceToContainerHandle::class);
			$events->listen(\Laravel\Octane\Events\TaskReceived::class, GiveNewApplicationInstanceToContainerHandle::class);
			$events->listen(\Laravel\Octane\Events\TickReceived::class, GiveNewApplicationInstanceToContainerHandle::class);
		}

		$viewFactory->addNamespace(GraphQLPlatform::NAMESPACE, __DIR__ . '/../resources/views');

		foreach ($graphQLConfigurator->schemas as $name => $configurator) {
			$schemaRegistry->register($name, $configurator);
		}

		if (!$this->app instanceof CachesRoutes || !$this->app->routesAreCached()) {
			foreach ($graphQLConfigurator->routes as $route) {
				$route($router, $urlGenerator);
			}
		}
	}

	private function registerUtils(): void
	{
		$this->app->singleton(NamingStrategy::class);
		$this->app->bind(NamingStrategyInterface::class, NamingStrategy::class);
		$this->app->singleton(ArgumentResolver::class);
		$this->app->singleton(InputTypeUtils::class);

		$this->app->singleton(
			DocBlockContextFactory::class,
			fn (Application $app) => new CachedDocBlockContextFactory(
				$app->make(ClassBoundCache::class),
				new PhpDocumentorDocBlockContextFactory(new ContextFactory())
			),
		);
		$this->app->singleton(
			DocBlockFactory::class,
			fn (Application $app) => new CachedDocBlockFactory(
				$app->make(ClassBoundCache::class),
				new PhpDocumentorDocBlockFactory(
					\phpDocumentor\Reflection\DocBlockFactory::createInstance(),
					$app->make(DocBlockContextFactory::class),
				),
			),
		);

		$this->app->singleton(AnnotationReader::class);
		$this->app->singleton(
			'graphqlite.expression_language',
			function (Application $app) {
				$expressionLanguage = new ExpressionLanguage($app->make('graphqlite.psr6_cache'));
				$expressionLanguage->registerProvider(new SecurityExpressionLanguageProvider());

				return $expressionLanguage;
			}
		);
	}

	private function registerCache(): void
	{
		$this->app->singleton('graphqlite.psr6_cache', function () {
			$namespace = 'graphqlite-' .
				mb_substr(md5(Versions::getVersion('thecodingmachine/graphqlite')), 0, 8) .
				'-' .
				mb_substr(md5(Versions::getVersion('tenantcloud/laravel-graphql-platform')), 0, 8);

			return extension_loaded('apcu') && ini_get('apc.enabled') ?
				new ApcuAdapter($namespace) :
				new PhpFilesAdapter($namespace);
		});
		$this->app->singleton(
			'graphqlite.psr16_cache',
			fn (Application $app) => new Psr16Cache($app->make('graphqlite.psr6_cache'))
		);

		$this->app->singleton(
			ClassBoundCache::class,
			fn (Application $app) => $app->make(GraphQLConfigurator::class)->devMode ?
				new FileModificationClassBoundCache($app->make('graphqlite.psr16_cache')) :
				new HardClassBoundCache($app->make('graphqlite.psr16_cache'))
		);

		$this->app->singleton(
			ClassFinderComputedCache::class,
			fn (Application $app) => $app->make(GraphQLConfigurator::class)->devMode ?
				new FileModificationClassFinderComputedCache($app->make('graphqlite.psr16_cache')) :
				new HardClassFinderComputedCache($app->make('graphqlite.psr16_cache'))
		);
	}

	private function registerSchema(): void
	{
		$this->app->singleton(
			GraphQLConfigurator::class,
			fn (Application $app) => new GraphQLConfigurator(devMode: $app->isLocal() || $app->runningUnitTests())
		);
		$this->app->singleton(SchemaRegistry::class, fn (Application $app) => new SchemaRegistry(
			$app->make(SchemaFactory::class),
			$app->factory(SchemaConfigurator::class),
		));
		$this->app->bind(WebonyxSchema::class, Schema::class);

		$this->app->singleton(
			SchemaConfigurator::class,
			fn (Application $app) => (new SchemaConfigurator())
				->addFieldMiddleware(new TransactionalFieldMiddleware())
				->addFieldMiddleware(new PreventLazyLoadingFieldMiddleware())
				->addFieldMiddleware(new SecurityFieldMiddleware(
					$app->make('graphqlite.expression_language'),
					$app->make(AuthenticationServiceInterface::class),
					$app->make(AuthorizationServiceInterface::class),
				))
				->addFieldMiddleware(new AuthorizationFieldMiddleware(
					$app->make(AuthenticationServiceInterface::class),
					$app->make(AuthorizationServiceInterface::class),
				))
				->addFieldMiddleware(new CostFieldMiddleware())
				->addInputFieldMiddleware(new MissingValueInputFieldMiddleware())
				->addInputFieldMiddleware(new IDInputFieldMiddleware(
					$app->make(ArgumentResolver::class),
				))
				->addInputFieldMiddleware(new ModelIDInputFieldMiddleware())
				->addInputFieldMiddleware(new SecurityInputFieldMiddleware(
					$app->make('graphqlite.expression_language'),
					$app->make(AuthenticationServiceInterface::class),
					$app->make(AuthorizationServiceInterface::class),
				))
				->addInputFieldMiddleware(new AuthorizationInputFieldMiddleware(
					$app->make(AuthenticationServiceInterface::class),
					$app->make(AuthorizationServiceInterface::class),
				))
				->addInputFieldMiddleware(new DescribeValidationInputFieldMiddleware(
					$app->make(MetadataFactoryInterface::class),
					new ReflectionConstraintDescriptionProvider(),
				))
				->addParameterMiddleware(new InjectUserParameterHandler($app->make(AuthenticationServiceInterface::class)))
				->addParameterMiddleware(new InjectSelectionParameterMiddleware())
				->addParameterMiddleware(new ModelIDParameterMiddleware())
				->addParameterMiddleware(new ResolveInfoParameterHandler())
				->addParameterMiddleware(new ContainerParameterHandler($app->make(self::CONTAINER_HANDLE)))
		);
	}

	private function registerHttp(): void
	{
		$this->app->bind(ServerRequestFactoryInterface::class, ServerRequestFactory::class);
		$this->app->bind(StreamFactoryInterface::class, StreamFactory::class);
		$this->app->bind(UploadedFileFactoryInterface::class, UploadedFileFactory::class);
		$this->app->bind(ResponseFactoryInterface::class, ResponseFactory::class);
		$this->app->bind(HttpMessageFactoryInterface::class, PsrHttpFactory::class);
		$this->app->singleton(HttpCodeDeciderInterface::class, GraphQLResponseHttpCodeDecider::class);
		$this->app->singleton(ServerConfig::class, static function (Application $app) {
			$serverConfig = new ServerConfig();
			$serverConfig->setErrorFormatter([WebonyxErrorHandler::class, 'errorFormatter']);
			$serverConfig->setErrorsHandler([WebonyxErrorHandler::class, 'errorHandler']);
			$serverConfig->setDebugFlag(
				$app->hasDebugModeEnabled() ?
					DebugFlag::RETHROW_UNSAFE_EXCEPTIONS | DebugFlag::INCLUDE_TRACE :
					DebugFlag::RETHROW_UNSAFE_EXCEPTIONS
			);
			$serverConfig->setValidationRules([
				...DocumentValidator::allRules(),
				...$app->make(GraphQLConfigurator::class)->validationRules,
			]);
			$serverConfig->setPersistedQueryLoader(
				$app->make(GraphQLConfigurator::class)->persistedQueryLoader
			);

			return $serverConfig;
		});
	}

	private function registerValidation(): void
	{
		$this->app->singleton(
			ValidatorInterface::class,
			fn (Application $app) => (new ValidatorBuilder())
				->enableAttributeMapping()
				->setMappingCache($app->make('graphqlite.psr6_cache'))
				->setTranslator(new LaravelCompositeTranslatorAdapter($app->make(Translator::class)))
				->setConstraintValidatorFactory(
					new SkipMissingValueConstraintValidatorFactory(
						new ContainerConstraintValidatorFactory($app->make(self::CONTAINER_HANDLE))
					)
				)
				->getValidator()
		);
		$this->app->bind(MetadataFactoryInterface::class, ValidatorInterface::class);
		$this->app->singleton(SymfonyInputTypeValidator::class);
		$this->app->bind(InputTypeValidatorInterface::class, SymfonyInputTypeValidator::class);
	}

	private function registerConnections(): void
	{
		Builder::macro('toGraphQLConnectable', function () {
			Assert::isInstanceOf($this, Builder::class);

			return new QueryBuilderConnectable($this);
		});
		EloquentBuilder::macro('toGraphQLConnectable', function () {
			Assert::isInstanceOf($this, EloquentBuilder::class);

			return new QueryBuilderConnectable($this);
		});
		Relation::macro('toGraphQLConnectable', function () {
			Assert::isInstanceOf($this, Relation::class);

			return new QueryBuilderConnectable($this);
		});
	}

	private function registerAuthentication(): void
	{
		$this->app->singleton(LaravelAuthenticationService::class, function () {
			$guard = config('graphqlite.guard', $this->app['config']['auth.defaults.guard']);

			if (!is_array($guard)) {
				$guard = [$guard];
			}

			return new LaravelAuthenticationService($guard);
		});

		$this->app->bind(AuthenticationServiceInterface::class, LaravelAuthenticationService::class);
		$this->app->bind(AuthorizationServiceInterface::class, LaravelAuthorizationService::class);
	}

	private function registerContainer(): void
	{
		$this->app->singleton(self::CONTAINER_HANDLE, LaravelContainerHandle::class);
	}
}
