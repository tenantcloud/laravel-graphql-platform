<?php

namespace TenantCloud\GraphQLPlatform\Validation\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use TenantCloud\GraphQLPlatform\Schema\OperationType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class GraphQLOperationType extends Constraint
{
	/**
	 * @param list<OperationType::*> $allowedTypes
	 */
	public function __construct(
		public readonly array $allowedTypes,
		mixed $options = null,
		array $groups = null,
		mixed $payload = null
	) {
		parent::__construct($options, $groups, $payload);
	}
}
