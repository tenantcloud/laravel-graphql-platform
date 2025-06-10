<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use Throwable;

class SubscriptionManager
{
	public function __construct(
		private readonly SubscriptionStorage $subscriptionStorage,
	) {}

	public function deactivate(Subscription $subscription, ?Throwable $reason = null): void
	{
		$this->subscriptionStorage->updateActive($subscription, false);

		$subscription->transport->deactivated($subscription, $reason);
	}

	public function delete(Subscription $subscription): void
	{
		$this->subscriptionStorage->delete($subscription);
	}
}
