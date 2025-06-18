<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Notifications;

use Illuminate\Support\Arr;

class GraphQLMessage
{
	/**
	 * @param list<string> $channels
	 */
	public function __construct(
		public readonly mixed $root,
		public array $channels = [],
	) {}

	/**
	 * @param list<string>|string $channels
	 */
	public function on(array|string $channels): self
	{
		$clone = clone $this;
		$clone->channels = [...$clone->channels, ...Arr::wrap($channels)];

		return $clone;
	}
}
