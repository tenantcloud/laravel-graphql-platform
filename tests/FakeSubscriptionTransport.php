<?php

namespace Tests;

use Carbon\CarbonImmutable;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use Throwable;

class FakeSubscriptionTransport implements SubscriptionTransport
{
	public const TYPE = 'fake';

	/** @var list<array{ Subscription, array<string, mixed> }> */
	public array $sent = [];

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
		$this->sent[] = [$subscription, $data];
	}

	public function deactivated(Subscription $subscription, ?Throwable $reason = null): void {}
}
