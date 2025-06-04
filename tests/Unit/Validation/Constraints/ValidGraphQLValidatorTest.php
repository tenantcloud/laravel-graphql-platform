<?php

namespace Tests\Unit\Validation\Constraints;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use TenantCloud\GraphQLPlatform\Schema\OperationType;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Validation\Constraints\ValidGraphQL;
use TenantCloud\GraphQLPlatform\Validation\Constraints\ValidGraphQLValidator;

/**
 * @extends ConstraintValidatorTestCase<ValidGraphQLValidator>
 */
class ValidGraphQLValidatorTest extends ConstraintValidatorTestCase
{
	#[DataProvider('passesValidationProvider')]
	public function testPassesValidation(string $operation): void
	{
		$this->validator->validate(
			Parser::parse(new Source($operation, 'GraphQL')),
			new ValidGraphQL('schema', [])
		);

		$this->assertNoViolation();
	}

	#[DataProvider('failsValidationProvider')]
	public function testFailsValidation(string $violationMessage, string $operation): void
	{
		$this->validator->validate(
			Parser::parse(new Source($operation, 'GraphQL')),
			new ValidGraphQL('schema', [])
		);

		$this->buildViolation($violationMessage)
			->assertRaised();
	}

	public function testThrowsUnexpectedValueForNonDocumentNodeValues(): void
	{
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessageMatches('/Expected argument of type "' . preg_quote(DocumentNode::class) . '", ".*" given/');

		$this->validator->validate('query {}', new ValidGraphQL('schema', []));
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
		yield [
			<<<'MD'
				Cannot query field "ids" on type "Query". Did you mean "id"?

				GraphQL (1:9)
				1: query { ids }
				           ^

				MD,
			'query { ids }',
		];
	}

	protected function createValidator(): ValidGraphQLValidator
	{
		return new ValidGraphQLValidator(
			schemaRegistry: mock(SchemaRegistry::class)
				->allows([
					'getOrFail' => new Schema([
						'query' => new ObjectType([
							'name'   => 'Query',
							'fields' => [
								'id' => [
									'type'    => Type::string(),
									'resolve' => static fn (): string => 'Your graphql-php endpoint is ready! Use a GraphQL client to explore the schema.',
								],
							],
						]),
					]),
				]),
			validationRules: DocumentValidator::allRules(),
		);
	}
}
