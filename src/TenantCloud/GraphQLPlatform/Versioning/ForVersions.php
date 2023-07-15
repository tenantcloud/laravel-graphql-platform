<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ForVersions implements MiddlewareAnnotationInterface
{
	public function __construct(
		public readonly string $constraint,
	) {
	}
}
