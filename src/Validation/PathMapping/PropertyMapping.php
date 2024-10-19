<?php

namespace TenantCloud\GraphQLPlatform\Validation\PathMapping;

class PropertyMapping
{
	private array $map = [];

	public function __construct() {}

	public function add(string $class, string $property, string $fieldName): void
	{
		$this->map[$class][$property] = $fieldName;
	}

	public function for(string $class, string $property): string
	{
		return $this->map[$class][$property];
	}
}
