<?php

namespace TenantCloud\GraphQLPlatform\Validation\PathMapping;

use Illuminate\Support\Str;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PropertyPathMapper
{
	public function __construct(
		private readonly PropertyMapping $propertyMapping,
		private readonly PropertyAccessor $propertyAccessor,
	) {}

	public function map(string $propertyPath, object|array $root): array
	{
		return Str::of($propertyPath)
			->explode('.')
			->flatMap(function (string $part) use (&$root) {
				[[$prefix, $prePaths], $propertyName, $subPaths] = $this->parsePropertyPathPart($part);

				if ($prefix) {
					$root = $this->propertyAccessor->getValue($root, $prefix);
				}

				if (!$propertyName) {
					return $prePaths;
				}

				$mappedName = $this->propertyMapping->for($root::class, $propertyName);

				// Get root for the next part
				$root = $this->propertyAccessor->getValue($root, $part);

				return [...$prePaths, $mappedName, ...$subPaths];
			})
			->all();
	}

	private function parsePropertyPathPart(string $part): array
	{
		preg_match_all('/\[([^\[\]]+)\]/A', $part, $prePathMatches);

		$prefix = implode('', $prePathMatches[0]);
		$part = Str::after($part, $prefix);

		return [
			[$prefix, $prePathMatches[1]],
			Str::match('/^([^\[\]]+)/', $part),
			Str::matchAll('/\[([^\[\]]+)\]/', $part)->all(),
		];
	}
}
