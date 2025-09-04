<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID;

use Attribute;
use RuntimeException;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotationInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class ModelID implements MiddlewareAnnotationInterface, ParameterAnnotationInterface
{
	public function __construct(
		public readonly bool $lockForUpdate = false,
	) {}

	/**
	 * @codeCoverageIgnore
	 */
	public function getTarget(): string
	{
		throw new RuntimeException();
	}
}
