<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Notifications;

use Illuminate\Support\Arr;

class GraphQLMessage
{
	public function __construct(
		public readonly mixed $root,
		public array $channels = [],
	)
	{
	}

	public function on(array|string $channels): self
	{
		$clone = clone $this;
		$clone->channels = [...$clone->channels, ...Arr::wrap($channels)];

		return $clone;
	}
}
