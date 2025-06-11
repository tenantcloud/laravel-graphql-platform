<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Executor\ExecutionResult;

class SubscriptionDataEmittedEvent
{
	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(
		public readonly Subscription $subscription,
		public readonly ExecutionResult $result,
		public readonly array $data,
	) {}
}
