<?php

namespace TenantCloud\GraphQLPlatform\Resolve;

readonly class ResolveKey
{
	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct(
		public string $typeName,
		public string $fieldName,
		public array $args,
	) {}
}
