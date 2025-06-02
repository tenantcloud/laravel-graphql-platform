<?php

namespace Tests;

use Carbon\CarbonImmutable;
use GraphQL\Error\Error;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

class FakeSubscriptionTransport implements SubscriptionTransport
{
	public const TYPE = 'fake';

	public function type(): string
	{
		return self::TYPE;
	}

	public function clientDetails(Subscription $subscription): array
	{
		return [
			'channel' => $subscription->channel,
		];
	}

	public function calculateExpiration(?Subscription $subscription): ?CarbonImmutable
	{
		return now()->toImmutable()->addHour();
	}

	public function send(Subscription $subscription, array $data): void
	{
	}

	public function unsubscribed(Subscription $subscription, SchemaNotFoundException|Error $exception): void
	{
	}
}
