<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use GraphQL\Language\AST\DocumentNode;
use Illuminate\Contracts\Auth\Authenticatable;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

class DatabaseSubscriptionStorage implements SubscriptionStorage
{
	public function subscribe(
		ChannelSubscription $channelSubscription,
		SubscriptionTransport $transport,
		string $schemaName,
		DocumentNode $document,
		array $variables,
		?CarbonImmutable $expiresAt = null,
		?Authenticatable $owner = null,
	): Subscription {
		$subscription = new GraphQLStoredSubscription();
		$subscription->owner()->associate($owner);
		$subscription->active = true;
		$subscription->channel = $channelSubscription->channel;
		$subscription->transport = $transport;
		$subscription->schema_name = $schemaName;
		$subscription->document = $document;
		$subscription->variables = $variables;
		$subscription->resolve = $channelSubscription->resolve;
		$subscription->filter = $channelSubscription->filter;
		$subscription->expires_at = $expiresAt;
		$subscription->save();

		return $subscription;
	}

	public function subscriptionById(string $id): ?Subscription
	{
		return GraphQLStoredSubscription::find($id);
	}

	public function activeSubscriptionsByChannels(array $channels): iterable
	{
		return GraphQLStoredSubscription::query()
			->where('active', true)
			->whereIn('channel', $channels)
			->lazy();
	}

	public function expired(): iterable
	{
		return GraphQLStoredSubscription::query()
			->whereNotNull('expires_at')
			->where('expires_at', '<=', now())
			->lazy();
	}

	public function updateExpiration(Subscription $subscription, ?CarbonImmutable $expiresAt): void
	{
		GraphQLStoredSubscription::query()
			->whereKey($subscription->id)
			->update([
				'expires_at' => $expiresAt,
			]);
	}

	public function updateActive(Subscription $subscription, bool $value): void
	{
		GraphQLStoredSubscription::query()
			->whereKey($subscription->id)
			->update([
				'active' => $value,
			]);
	}

	public function delete(Subscription $subscription): void
	{
		GraphQLStoredSubscription::destroy($subscription->id);
	}
}
