<?php

namespace TenantCloud\GraphQLPlatform\Resolve;

readonly class ResolveKey
{
	private string $hash;

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct(
		public string $typeName,
		public string $fieldName,
		public array $args,
	) {}

	public function hash(): string
	{
		return $this->hash ??= md5(serialize($this));
	}
}
