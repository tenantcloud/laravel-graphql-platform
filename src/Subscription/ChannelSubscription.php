<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use TenantCloud\GraphQLPlatform\Subscription\Notifications\GraphQLMessage;

/**
 * A "configuration" for a subscription that should be returned from #[Subscription] annotated controller methods.
 *
 * @template TOutput
 */
readonly class ChannelSubscription
{
	/**
	 * @param string                            $channel Channel name that should match one of the channels specified in a notification using {@see GraphQLMessage}
	 * @param (callable(TOutput): TOutput)|null $resolve A method that will be called every time a new item is dispatched
	 * @param (callable(TOutput): bool)|null    $filter  A method that will be called every time a new item is dispatched, allowing to skip that particular item
	 */
	public function __construct(
		public string $channel,
		public mixed $resolve = null,
		public mixed $filter = null,
	) {}

	/**
	 * @return self<TOutput>
	 */
	public function withChannel(string $channel): self
	{
		return new self(
			channel: $channel,
			resolve: $this->resolve,
			filter: $this->filter,
		);
	}
}
