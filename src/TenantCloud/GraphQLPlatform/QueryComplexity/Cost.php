<?php

namespace TenantCloud\GraphQLPlatform\QueryComplexity;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Cost implements MiddlewareAnnotationInterface
{
	public function __construct(
		public readonly int $complexity = 1,
		public readonly ?int $defaultMultiplier = null,
		public readonly array $multipliers = [],
	)
	{
	}
}
