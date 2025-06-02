<?php

namespace TenantCloud\GraphQLPlatform\Validation\Constraints;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Utils\AST;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class GraphQLOperationTypeValidator extends ConstraintValidator
{
	public function validate(mixed $value, Constraint $constraint): void
	{
		if (!$constraint instanceof GraphQLOperationType) {
			throw new UnexpectedTypeException($constraint, GraphQLOperationType::class);
		}

		if ($value === null) {
			return;
		}

		if (!$value instanceof DocumentNode) {
			throw new UnexpectedValueException($value, DocumentNode::class);
		}

		$operationAST = AST::getOperationAST($value);

		if ($operationAST === null) {
			$this->context->addViolation('Document must contain exactly one operation.');

			return;
		}

		if (!in_array($operationAST->operation, $constraint->allowedTypes)) {
			$this->context->addViolation('Document operation must be one of those types: ' . implode(', ', $constraint->allowedTypes));
		}
	}
}
