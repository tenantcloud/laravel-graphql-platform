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

	public function canceled(Subscription $subscription, SchemaNotFoundException|Error|null $reason = null): void {}
}
