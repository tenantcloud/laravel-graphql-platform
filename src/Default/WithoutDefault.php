<?php

namespace TenantCloud\GraphQLPlatform\Default;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class WithoutDefault implements MiddlewareAnnotationInterface
{
	/**
	 * @param class-string<MiddlewareAnnotationInterface>|list<class-string<MiddlewareAnnotationInterface>> $attributes
	 */
	public function __construct(
		public readonly string|array $attributes,
	)
	{
	}
}
