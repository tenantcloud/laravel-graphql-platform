<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class Transactional implements MiddlewareAnnotationInterface
{
}
