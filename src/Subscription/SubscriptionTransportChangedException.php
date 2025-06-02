<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Error\Error;

class SubscriptionTransportChangedException extends Error
{
	public const CODE = 'SUBSCRIPTION_TRANSPORT_CHANGED';

	public function __construct(
		public readonly Subscription $subscription,
	)
	{
		parent::__construct('Subscriptions require a different transport. See error extensions for details on how to continue with the subscription.');

		$this->code = self::CODE;
	}

	/** @return array<string, mixed> */
	public function getExtensions(): array
	{
		return [
			'code' => $this->code,
			'subscription' => [
				'transport' => [
					'type' => $this->subscription->transport->type(),
					...$this->subscription->transport->clientDetails($this->subscription),
				],
			],
		];
	}

	public function isClientSafe(): bool
	{
		return true;
	}
}
