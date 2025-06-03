<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use GraphQL\Language\AST\DocumentNode;
use Illuminate\Contracts\Database\Eloquent\Builder;
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
		CarbonImmutable $expiresAt = null
	): Subscription {
		$subscription = new GraphQLStoredSubscription();
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

	public function subscriptionsByChannels(array $channels): iterable
	{
		return GraphQLStoredSubscription::query()
			->whereIn('channel', $channels)
			->lazy();
	}

	public function unsubscribe(string $id): void
	{
		GraphQLStoredSubscription::destroy($id);
	}

	public function expired(): iterable
	{
		return GraphQLStoredSubscription::query()
			->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '<=', now()))
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
}
