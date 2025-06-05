<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\ErrorHelper;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use Throwable;

class SubscriptionManager
{
	public function __construct(
		private readonly SubscriptionStorage $subscriptionStorage,
		private readonly SchemaRegistry $schemaRegistry,
		private readonly GraphQLPlatform $graphQLPlatform,
	) {}

	public function send(Subscription $subscription, mixed $root): void
	{
		try {
			$schema = $this->schemaRegistry->getOrFail($subscription->schema_name);
		} catch (SchemaNotFoundException $exception) {
			$this->deactivate($subscription, $exception);

			return;
		}

		if (with($root, $subscription->filter) === false) {
			return;
		}

		$result = $this->executeForRoot($schema, $subscription, $root);

		if ($exception = ErrorHelper::malformedError($result)) {
			$this->deactivate($subscription, $exception);

			return;
		}

		// ->toArray() might re-throw some internal errors, in which case this job will fail as expected.
		// Otherwise, for non-internal errors, they will be passed to the subscription.
		$subscription->transport->send($subscription, $result->toArray());
	}

	public function deactivate(Subscription $subscription, ?Throwable $reason = null): void
	{
		$this->subscriptionStorage->updateActive($subscription, false);

		$subscription->transport->deactivated($subscription, $reason);
	}

	public function delete(Subscription $subscription): void
	{
		$this->subscriptionStorage->delete($subscription);
	}

	private function executeForRoot(Schema $schema, Subscription $subscription, mixed $root): ExecutionResult
	{
		return $this->graphQLPlatform->executeQuery(
			schema: $schema,
			source: $subscription->document,
			rootValue: new SubscriptionRootContainer($root, $subscription->resolve),
			variableValues: $subscription->variables,
		);
	}
}
