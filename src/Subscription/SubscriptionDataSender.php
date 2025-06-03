<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\ErrorHelper;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use Webmozart\Assert\Assert;

class SubscriptionDataSender
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
			$this->unsubscribe($subscription, $exception);

			return;
		}

		if (with($root, $subscription->filter) === false) {
			return;
		}

		$result = $this->executeForRoot($schema, $subscription, $root);

		if ($exception = ErrorHelper::malformedError($result)) {
			$this->unsubscribe($subscription, $exception);

			return;
		}

		// ->toArray() might re-throw some internal errors, in which case this job will fail as expected.
		// Otherwise, for non-internal errors, they will be passed to the subscription.
		$subscription->transport->send($subscription, $result->toArray());
	}

	private function unsubscribe(Subscription $subscription, SchemaNotFoundException|Error $exception): void
	{
		// There's no point in continuing if the schema/query isn't available anymore.
		$this->subscriptionStorage->unsubscribe($subscription->id);

		$subscription->transport->unsubscribed($subscription, $exception);
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
