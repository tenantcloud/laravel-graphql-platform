<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;
use UnitEnum;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
readonly class Authorize implements MiddlewareAnnotationInterface
{
	/**
	 * Just like regular Laravel's ->authorize() - with the exception that {@see AuthorizePlaceholder} is substituted with the respective value.
	 *
	 * @param list<mixed> $arguments
	 */
	public function __construct(
		public string|UnitEnum $ability,
		public array $arguments = [AuthorizePlaceholder::THIS],
	) {}
}
