<?php

namespace TenantCloud\GraphQLPlatform;

use GraphQL\Error\DebugFlag;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema as WebonyxSchema;
use GraphQL\Validator\DocumentValidator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Mouf\Composer\ClassNameMapper;
use PackageVersions\Versions;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Command\DebugCommand;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use TenantCloud\GraphQLPlatform\Http\GraphQLResponseHttpCodeDecider;
use TenantCloud\GraphQLPlatform\ID\IDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Auth\LaravelAuthenticationService;
use TenantCloud\GraphQLPlatform\Laravel\Auth\LaravelAuthorizationService;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ModelIDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ModelIDParameterMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation\PreventLazyLoadingFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\TransactionalFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LaravelPaginationFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\SanePsr11LaravelContainerAdapter;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\QueryComplexity\ComplexityFieldMiddleware;
use TenantCloud\GraphQLPlatform\Schema\NullAnnotationReader;
use TenantCloud\GraphQLPlatform\Schema\PrintCommand;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaFactory;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Selection\InjectSelectionParameterMiddleware;
use TenantCloud\GraphQLPlatform\Validation\LaravelCompositeTranslatorAdapter;
use TenantCloud\GraphQLPlatform\Validation\SkipMissingValueConstraintValidatorFactory;
use TenantCloud\GraphQLPlatform\Validation\SymfonyInputTypeValidator;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use TheCodingMachine\GraphQLite\InputTypeUtils;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ContainerParameterHandler;
use TheCodingMachine\GraphQLite\Mappers\Parameters\InjectUserParameterHandler;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ResolveInfoParameterHandler;
use TheCodingMachine\GraphQLite\Middlewares\AuthorizationFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\AuthorizationInputFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\SecurityFieldMiddleware;
use TheCodingMachine\GraphQLite\Middlewares\SecurityInputFieldMiddleware;
use TheCodingMachine\GraphQLite\NamingStrategy;
use TheCodingMachine\GraphQLite\NamingStrategyInterface;
use TheCodingMachine\GraphQLite\Reflection\CachedDocBlockFactory;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;
use TheCodingMachine\GraphQLite\Security\SecurityExpressionLanguageProvider;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;
use TheCodingMachine\GraphQLite\Utils\NamespacedCache;
use TheCodingMachine\GraphQLite\Utils\Namespaces\NamespaceFactory;

class GraphQLPlatformServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->bind(WebonyxSchema::class, Schema::class);
		$this->app->bind(ServerRequestFactoryInterface::class, ServerRequestFactory::class);
		$this->app->bind(StreamFactoryInterface::class, StreamFactory::class);
		$this->app->bind(UploadedFileFactoryInterface::class, UploadedFileFactory::class);
		$this->app->bind(ResponseFactoryInterface::class, ResponseFactory::class);
		$this->app->bind(HttpMessageFactoryInterface::class, PsrHttpFactory::class);
		$this->app->singleton(HttpCodeDeciderInterface::class, GraphQLResponseHttpCodeDecider::class);
		$this->app->singleton(GraphQLConfigurator::class);
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

		$this->app->singleton('graphqliteCache', static function () {
			if (extension_loaded('apcu') && ini_get('apc.enabled')) {
				return new Psr16Cache(new ApcuAdapter());
			}

			return new Psr16Cache(new PhpFilesAdapter());
		});

		$this->app->singleton(LaravelAuthenticationService::class, function (Application $app) {
			$guard = config('graphqlite.guard', $this->app['config']['auth.defaults.guard']);

			if (!is_array($guard)) {
				$guard = [$guard];
			}

			return new LaravelAuthenticationService($guard);
		});

		$this->app->bind(AuthenticationServiceInterface::class, LaravelAuthenticationService::class);
		$this->app->bind(AuthorizationServiceInterface::class, LaravelAuthorizationService::class);

		$this->app->singleton(SanePsr11LaravelContainerAdapter::class);
		$this->app->singleton(
			'graphqlite.symfony_cache',
			fn (Application $app) => new Psr16Adapter(
				$app->make('graphqliteCache'),
				mb_substr(md5(Versions::getVersion('thecodingmachine/graphqlite')), 0, 8)
			)
		);
		$this->app->singleton(
			'graphqlite.namespaced_cache',
			fn (Application $app) => new NamespacedCache($app->make('graphqliteCache')),
		);
		$this->app->singleton(
			AnnotationReader::class,
			fn () => new AnnotationReader(new NullAnnotationReader(), AnnotationReader::LAX_MODE)
		);
		$this->app->singleton(
			'graphqlite.expression_language',
			function (Application $app) {
				$expressionLanguage = new ExpressionLanguage($app->make('graphqlite.symfony_cache'));
				$expressionLanguage->registerProvider(new SecurityExpressionLanguageProvider());

				return $expressionLanguage;
			}
		);
		$this->app->singleton(
			ClassNameMapper::class,
			fn () => ClassNameMapper::createFromComposerFile(null, null, true),
		);
		$this->app->singleton(NamingStrategy::class);
		$this->app->bind(NamingStrategyInterface::class, NamingStrategy::class);
		$this->app->singleton(ArgumentResolver::class);
		$this->app->singleton(InputTypeUtils::class);
		$this->app->singleton(
			NamespaceFactory::class,
			fn (Application $app) => new NamespaceFactory(
				$app->make('graphqlite.namespaced_cache'),
				$app->make(ClassNameMapper::class),
				null
			)
		);
		$this->app->singleton(
			CachedDocBlockFactory::class,
			fn (Application $app) => new CachedDocBlockFactory($app->make('graphqlite.namespaced_cache'))
		);
		$this->app->singleton(
			ValidatorInterface::class,
			fn (Application $app) => (new ValidatorBuilder())
				->enableAnnotationMapping()
				->setMappingCache($app->make('graphqlite.symfony_cache'))
				->setTranslator(new LaravelCompositeTranslatorAdapter($app->make(Translator::class)))
				->setConstraintValidatorFactory(
					new SkipMissingValueConstraintValidatorFactory(
						new ContainerConstraintValidatorFactory($app->make(SanePsr11LaravelContainerAdapter::class))
					)
				)
				->getValidator()
		);
		$this->app->bind(MetadataFactoryInterface::class, ValidatorInterface::class);
		$this->app->singleton(SymfonyInputTypeValidator::class);
		$this->app->bind(InputTypeValidatorInterface::class, SymfonyInputTypeValidator::class);
		$this->app->singleton(
			SchemaConfigurator::class,
			fn (Application $app) => (new SchemaConfigurator())
				->addFieldMiddleware(new LaravelPaginationFieldMiddleware())
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
				->addFieldMiddleware(new ComplexityFieldMiddleware())
				->addInputFieldMiddleware(new MissingValueInputFieldMiddleware())
				->addInputFieldMiddleware(new IDInputFieldMiddleware())
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
//				->addInputFieldMiddleware(new DescribeValidationInputFieldMiddleware(
//					$app->make(MetadataFactoryInterface::class),
//					new ReflectionConstraintDescriptionProvider(),
//				))
				->addParameterMiddleware(new InjectUserParameterHandler($app->make(AuthenticationServiceInterface::class)))
				->addParameterMiddleware(new InjectSelectionParameterMiddleware())
				->addParameterMiddleware(new ModelIDParameterMiddleware())
				->addParameterMiddleware(new ResolveInfoParameterHandler())
//				->addParameterMiddleware(new PrefetchParameterHandler($fieldsBuilder, $app->make(SanePsr11LaravelContainerAdapter::class)))
				->addParameterMiddleware(new ContainerParameterHandler($app->make(SanePsr11LaravelContainerAdapter::class)))
		);

		$this->app->singleton(SchemaRegistry::class, fn (Application $app) => new SchemaRegistry(
			$app->make(SchemaFactory::class),
			$app->factory(SchemaConfigurator::class),
		));
	}

	public function boot(
		GraphQLConfigurator $graphQLConfigurator,
		Factory $viewFactory,
		Router $router,
		UrlGenerator $urlGenerator,
		SchemaRegistry $schemaRegistry,
	): void {
		if ($this->app->runningInConsole()) {
			$this->commands([
				DebugCommand::class,
				PrintCommand::class,
			]);
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
}
