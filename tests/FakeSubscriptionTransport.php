<?php

namespace Tests;

use Carbon\CarbonImmutable;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use Throwable;

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

	public function emit(Subscription $subscription, array $data): void {}

	public function deactivated(Subscription $subscription, ?Throwable $reason = null): void {}
}
