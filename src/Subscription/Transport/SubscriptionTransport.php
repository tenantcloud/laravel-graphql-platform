<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Transport;

use Carbon\CarbonImmutable;
use GraphQL\Error\Error;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;

/**
 * Subscriptions cannot be returned as SSE stream or switched to WS directly from a Laravel PHP server,
 * so subscription transport acts as man-in-the-middle for the data that is sent to the subscription.
 *
 * E.g. after a subscription is stored in storage, this transport is used to communicate with the client somehow.
 */
interface SubscriptionTransport
{
	/**
	 * Type that's used for storage and sent to the client to they can differentiate between different transports.
	 *
	 * Example: pusher, webhooks, mercure
	 */
	public function type(): string;

	/**
	 * Details objects that is sent to the client after they subscribe, so they know what to do next.
	 *
	 * @return array<string, mixed>
	 */
	public function clientDetails(Subscription $subscription): array;

	/**
	 * Calculates the next expiration date. If you return `null`, the subscription will never expire.
	 *
	 * @param Subscription|null $subscription Null when subscription is just being created (new)
	 */
	public function calculateExpiration(?Subscription $subscription): ?CarbonImmutable;

	/**
	 * Send already serialized $data to given subscription. That may be a push to a Pusher channel or a webhook for example.
	 *
	 * @param array<string, mixed> $data
	 */
	public function send(Subscription $subscription, array $data): void;

	/**
	 * Called when a subscription is cancelled/deleted.
	 */
	public function canceled(Subscription $subscription, SchemaNotFoundException|Error|null $reason = null): void;
}
