<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\ErrorHelper;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;

class SubscriptionEmitter
{
	public function __construct(
		private readonly SubscriptionStorage $subscriptionStorage,
		private readonly SubscriptionManager $subscriptionManager,
		private readonly SchemaRegistry $schemaRegistry,
		private readonly GraphQLPlatform $graphQLPlatform,
		private readonly Dispatcher $events,
	) {}

	/**
	 * @param list<string>|string $channels
	 */
	public function emit(array|string $channels, mixed $root): void
	{
		$subscriptions = $this->subscriptionStorage->activeSubscriptionsByChannels(Arr::wrap($channels));

		foreach ($subscriptions as $subscription) {
			$this->emitForSubscription($subscription, $root);
		}
	}

	public function emitForSubscription(Subscription $subscription, mixed $root): void
	{
		try {
			$schema = $this->schemaRegistry->getOrFail($subscription->schema_name);
		} catch (SchemaNotFoundException $exception) {
			$this->subscriptionManager->deactivate($subscription, $exception);

			return;
		}

		if (with($root, $subscription->filter) === false) {
			return;
		}

		$result = $this->graphQLPlatform->executeQuery(
			schema: $schema,
			source: $subscription->document,
			rootValue: new SubscriptionRootContainer($root, $subscription->resolve),
			variableValues: $subscription->variables,
		);

		if ($exception = ErrorHelper::malformedError($result)) {
			$this->subscriptionManager->deactivate($subscription, $exception);

			return;
		}

		$data = $result->toArray();

		// ->toArray() might re-throw some internal errors, in which case this job will fail as expected.
		// Otherwise, for non-internal errors, they will be passed to the subscription.
		$subscription->transport->emit($subscription, $data);

		$this->events->dispatch(new SubscriptionDataEmittedEvent($subscription, $result, $data));
	}
}
