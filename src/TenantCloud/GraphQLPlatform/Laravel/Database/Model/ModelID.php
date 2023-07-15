<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model;

use Attribute;
use RuntimeException;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotationInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class ModelID implements ParameterAnnotationInterface
{
	public function __construct(
		public readonly ?bool $lockForUpdate = null
	) {
	}

	public function getTarget(): string
	{
		throw new RuntimeException();
	}
}
