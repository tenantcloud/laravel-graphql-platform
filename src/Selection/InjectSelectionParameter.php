<?php

namespace TenantCloud\GraphQLPlatform\Selection;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

/**
 * Handles {@see InjectSelection}.
 */
class InjectSelectionParameter implements ParameterInterface
{
	public function __construct(
		private readonly ?string $prefix,
	) {}

	public function resolve(?object $source, array $args, mixed $context, ResolveInfo $info): mixed
	{
		$selection = $info->getFieldSelection(PHP_INT_MAX);

		if ($this->prefix) {
			$selection = Arr::get($selection, $this->prefix) ?? [];

			// If sub selection is a boolean (i.e. for whatever reason selected as a non-object field), treat is as no sub selection.
			if (!is_array($selection)) {
				return [];
			}
		}

		return $selection;
	}
}
