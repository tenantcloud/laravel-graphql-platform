<?php

namespace TenantCloud\GraphQLPlatform\Validation\Constraints;

use Attribute;
use GraphQL\Type\Schema;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ValidGraphQL extends Constraint
{
	/**
	 * @param string|Schema|(callable(): string|Schema) $schema
	 */
	public function __construct(
		public readonly mixed $schema,
		public readonly array $variables,
		mixed $options = null,
		array $groups = null,
		mixed $payload = null
	) {
		parent::__construct($options, $groups, $payload);
	}
}
