<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use GraphQL\Language\AST\DocumentNode;
use Illuminate\Contracts\Auth\Authenticatable;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

interface SubscriptionStorage
{
	/**
	 * @param array<string, mixed> $variables
	 * @param ChannelSubscription<*> $channelSubscription
	 */
	public function subscribe(
		ChannelSubscription $channelSubscription,
		SubscriptionTransport $transport,
		string $schemaName,
		DocumentNode $document,
		array $variables,
		?CarbonImmutable $expiresAt = null,
		?Authenticatable $owner = null,
	): Subscription;

	public function subscriptionById(string $id): ?Subscription;

	/**
	 * @param list<string> $channels
	 *
	 * @return iterable<Subscription>
	 */
	public function activeSubscriptionsByChannels(array $channels): iterable;

	/**
	 * @return iterable<Subscription>
	 */
	public function expired(): iterable;

	public function updateExpiration(Subscription $subscription, ?CarbonImmutable $expiresAt): void;

	public function updateActive(Subscription $subscription, bool $value): void;

	public function delete(Subscription $subscription): void;
}
