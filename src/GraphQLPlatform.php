<?php

namespace TenantCloud\GraphQLPlatform;

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionTransportChangedException;

final class GraphQLPlatform
{
	public const NAMESPACE = 'graphql_platform';

	public function __construct(
		private readonly ServerConfig $serverConfig,
	)
	{
	}

	public static function namespaced(string $item): string
	{
		return self::NAMESPACE . '::' . $item;
	}

	public function executeQuery(
		Schema $schema,
		string|DocumentNode $source,
		mixed $rootValue = null,
		callable $applyContext = null,
		?array $variableValues = null,
		?string $operationName = null,
		?callable $fieldResolver = null,
		?array $validationRules = null
	): ExecutionResult {
		$context = new Context();
		$context = with($context, $applyContext);

		if ($validationRules === null) {
			$validationRules = is_callable($this->serverConfig->getValidationRules()) ?
				$this->serverConfig->getValidationRules()() :
				$this->serverConfig->getValidationRules();
		}

		$result = GraphQL::executeQuery(
			schema: $schema,
			source: $source,
			rootValue: $rootValue,
			contextValue: $context,
			variableValues: $variableValues,
			operationName: $operationName,
			fieldResolver: $fieldResolver,
			validationRules: $validationRules,
		);

		// Set the correct error handler/formatter so that if ->toArray() is called, it serializes as expected.
		$result->setErrorsHandler($this->serverConfig->getErrorsHandler());
		$result->setErrorFormatter($this->serverConfig->getErrorFormatter());

		return $result;
	}
}
