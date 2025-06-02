<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Server\Exception\BatchedQueriesAreNotSupported;
use GraphQL\Server\Exception\FailedToDetermineOperationType;
use GraphQL\Server\Exception\GetMethodSupportsOnlyQueryOperation;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\RequestError;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\ErrorHelper;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use TheCodingMachine\GraphQLite\Context\Context;
use Webmozart\Assert\Assert;

class SubscriptionDataSender
{
	public function __construct(
		private readonly SubscriptionStorage $subscriptionStorage,
		private readonly SchemaRegistry $schemaRegistry,
		private readonly ServerConfig $config,
		private readonly Helper $serverHelper,
	)
	{
	}

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
		$config = clone $this->config;
		$config->setSchema($schema);
		$config->setRootValue(new SubscriptionRootContainer($root, $subscription->resolve));

		// Just in case, make sure we didn't accidentally mutate the original config. This would be very very very bad.
		Assert::null($this->config->getRootValue());

		return $this->serverHelper->executeOperation($config, OperationParams::create([
			'operation' => $subscription->document,
			'variables' => $subscription->variables,
		]));
	}
}
