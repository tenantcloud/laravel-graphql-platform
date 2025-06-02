<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use GraphQL\Language\AST\DocumentNode;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

interface SubscriptionStorage
{
	public function subscribe(
		ChannelSubscription $channelSubscription,
		SubscriptionTransport $transport,
		string $schemaName,
		DocumentNode $document,
		array $variables,
		CarbonImmutable $expiresAt = null
	): Subscription;

	public function subscriptionById(string $id): ?Subscription;

	/**
	 * @return iterable<Subscription>
	 */
	public function subscriptionsByOwnerChannels(array $channels): iterable;

	public function unsubscribe(string $id): void;

	/**
	 * @return iterable<Subscription>
	 */
	public function expired(): iterable;

	public function updateExpiration(Subscription $subscription, ?CarbonImmutable $expiresAt): void;
}
