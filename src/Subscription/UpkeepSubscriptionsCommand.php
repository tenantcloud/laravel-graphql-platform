<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use Illuminate\Console\Command;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;

class UpkeepSubscriptionsCommand extends Command
{
	protected $signature = 'graphql:subscriptions:upkeep';

	protected $description = 'Deletes or extends expiration for subscriptions.';

	public function handle(SubscriptionStorage $subscriptionStorage, SubscriptionManager $subscriptionManager): int
	{
		foreach ($subscriptionStorage->expired() as $subscription) {
			$newExpiration = $subscription->transport->calculateExpiration($subscription);

			if ($newExpiration?->isPast()) {
				$subscriptionManager->delete($subscription);

				continue;
			}

			$subscriptionStorage->updateExpiration($subscription, $newExpiration);
		}

		$this->info('Canceled or updated expirations for all expiring subscriptions.');

		return self::SUCCESS;
	}
}
