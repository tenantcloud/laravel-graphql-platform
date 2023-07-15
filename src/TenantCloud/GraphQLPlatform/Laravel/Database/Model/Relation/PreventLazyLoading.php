<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class PreventLazyLoading implements MiddlewareAnnotationInterface
{
}
