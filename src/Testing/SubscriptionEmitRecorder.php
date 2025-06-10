<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use Illuminate\Contracts\Events\Dispatcher;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionDataEmittedEvent;

class SubscriptionEmitRecorder
{
	/**
	 * @param list<TestExecutionResult> $data
	 */
	public function __construct(
		private array $data = [],
	) {}

	public static function recordFromEvents(Dispatcher $dispatcher): self
	{
		$that = new self();

		$dispatcher->listen(
			fn (SubscriptionDataEmittedEvent $event) => $that->data[] = TestExecutionResult::fromExecutionResult($event->result),
		);

		return $that;
	}

	/**
	 * @return list<TestExecutionResult>
	 */
	public function all(): array
	{
		return $this->data;
	}
}
