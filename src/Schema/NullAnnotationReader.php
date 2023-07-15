<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class NullAnnotationReader implements Reader
{
	public function getClassAnnotations(ReflectionClass $class): array
	{
		return [];
	}

	public function getClassAnnotation(ReflectionClass $class, $annotationName)
	{
		return null;
	}

	public function getMethodAnnotations(ReflectionMethod $method): array
	{
		return [];
	}

	public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
	{
		return null;
	}

	public function getPropertyAnnotations(ReflectionProperty $property): array
	{
		return [];
	}

	public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
	{
		return null;
	}
}
