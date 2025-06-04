<?php

namespace TenantCloud\GraphQLPlatform;

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use GraphQL\Validator\Rules\ValidationRule;
use TenantCloud\GraphQLPlatform\Context\Context;
use Webmozart\Assert\Assert;

final class GraphQLPlatform
{
	public const NAMESPACE = 'graphql_platform';

	public function __construct(
		private readonly ServerConfig $serverConfig,
	) {}

	public static function namespaced(string $item): string
	{
		return self::NAMESPACE . '::' . $item;
	}

	/**
	 * @param (callable(Context): Context)|null $applyContext
	 * @param array<string, mixed>|null         $variableValues
	 * @param list<ValidationRule>|null         $validationRules
	 */
	public function executeQuery(
		Schema $schema,
		string|DocumentNode $source,
		mixed $rootValue = null,
		callable $applyContext = null,
		array $variableValues = null,
		string $operationName = null,
		callable $fieldResolver = null,
		array $validationRules = null
	): ExecutionResult {
		$context = new Context();
		$context = with($context, $applyContext);

		if ($validationRules === null) {
			// We don't support callable() version of it for now, because it requires
			// OperationsParams as well as parsed document (source), neither of which we have here
			Assert::nullOrIsArray($this->serverConfig->getValidationRules());

			$validationRules = $this->serverConfig->getValidationRules();
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
