<?php

namespace Tests\Unit\Validation\Constraints;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use TenantCloud\GraphQLPlatform\Schema\OperationType;
use TenantCloud\GraphQLPlatform\Validation\Constraints\GraphQLOperationType;
use TenantCloud\GraphQLPlatform\Validation\Constraints\GraphQLOperationTypeValidator;
use Tests\TestCase;

class GraphQLOperationTypeValidatorTest extends ConstraintValidatorTestCase
{
	protected function createValidator(): GraphQLOperationTypeValidator
	{
		return new GraphQLOperationTypeValidator();
	}

	/**
	 * @dataProvider passesValidationProvider
	 */
	public function testPassesValidation(string $operation, array $allowedTypes)
	{
		$this->validator->validate(
			Parser::parse(new Source($operation, 'GraphQL')),
			new GraphQLOperationType($allowedTypes)
		);

		$this->assertNoViolation();
	}

	/**
	 * @dataProvider failsValidationProvider
	 */
	public function testFailsValidation(string $violationMessage, string $operation, array $allowedTypes)
	{
		$this->validator->validate(
			Parser::parse(new Source($operation, 'GraphQL')),
			new GraphQLOperationType($allowedTypes)
		);

		$this->buildViolation($violationMessage)
			->assertRaised();
	}

	public function testThrowsUnexpectedValueForNonDocumentNodeValues(): void
	{
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessageMatches('/Expected argument of type "' . preg_quote(DocumentNode::class) . '", ".*" given/');

		$this->validator->validate("query {}", new GraphQLOperationType([]));
	}

	public static function passesValidationProvider(): iterable
	{
		yield ['{ id }', [OperationType::QUERY]];
		yield ['query { id }', [OperationType::QUERY]];
		yield ['query { id }', [OperationType::QUERY, OperationType::MUTATION, OperationType::SUBSCRIPTION]];
		yield ['query Named { id }', [OperationType::QUERY]];
		yield ['mutation { id }', [OperationType::MUTATION]];
		yield ['subscription { id }', [OperationType::SUBSCRIPTION]];
	}

	public static function failsValidationProvider(): iterable
	{
		yield ['Document must contain exactly one operation.', 'query { id } query { id }', [OperationType::QUERY]];
		yield ['Document must contain exactly one operation.', 'query Named1 { id } query Named2 { id }', [OperationType::QUERY]];
		yield ['Document must contain exactly one operation.', 'type Something { id: String }', [OperationType::QUERY]];
		yield ['Document operation must be one of those types: subscription', 'query { id }', [OperationType::SUBSCRIPTION]];
		yield ['Document operation must be one of those types: subscription', '{ id }', [OperationType::SUBSCRIPTION]];
		yield ['Document operation must be one of those types: subscription', 'mutation { id }', [OperationType::SUBSCRIPTION]];
		yield ['Document operation must be one of those types: query, mutation', 'subscription { id }', [OperationType::QUERY, OperationType::MUTATION]];
	}
}
