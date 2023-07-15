<?php

namespace TenantCloud\GraphQLPlatform\ID;

use Attribute;
use RuntimeException;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ID implements MiddlewareAnnotationInterface
{
	public function getTarget(): string
	{
		throw new RuntimeException();
	}
}
