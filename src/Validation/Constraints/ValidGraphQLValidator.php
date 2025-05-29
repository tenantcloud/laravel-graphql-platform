<?php

namespace TenantCloud\GraphQLPlatform\Validation\Constraints;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;

class ValidGraphQLValidator extends ConstraintValidator
{
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
		private readonly array $validationRules,
	)
	{
	}

	public function validate(mixed $value, Constraint $constraint): void
	{
		if (!$constraint instanceof ValidGraphQL) {
			throw new UnexpectedTypeException($constraint, ValidGraphQL::class);
		}

		if ($value === null) {
			return;
		}

		if (!$value instanceof DocumentNode) {
			throw new UnexpectedValueException($value, DocumentNode::class);
		}

		$errors = DocumentValidator::validate(
			$this->resolveSchema($constraint->schema),
			$value,
			$this->resolveRules($constraint->variables),
		);

		foreach ($errors as $error) {
			$this->context->addViolation((string) $error);
		}
	}

	private function resolveSchema(string|callable $schemaName): Schema
	{
		$schema = is_string($schemaName) ? $schemaName : $schemaName();

		return is_string($schema) ? $this->schemaRegistry->getOrFail($schema) : $schema;
	}

	private function resolveRules(array $variableValues): array
	{
		foreach ($this->validationRules as $rule) {
			if ($rule instanceof QueryComplexity) {
				$rule->setRawVariableValues($variableValues);
			}
		}

		return $this->validationRules;
	}
}
