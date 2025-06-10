<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionDataEmittedEvent;

class TestExecutionResultEmitted
{
	use Macroable;

	/**
	 * @param list<TestExecutionResult> $data
	 */
	public function __construct(
		private array $data = [],
	) {}

	public static function fromEvents(Dispatcher $dispatcher): self
	{
		$that = new self();

		$dispatcher->listen(
			fn (SubscriptionDataEmittedEvent $event) => $that->data[] = TestExecutionResult::fromExecutionResult($event->result),
		);

		return $that;
	}

	public function assertTimes(int $times): self
	{
		Assert::assertCount($times, $this->data);

		return $this;
	}

	/**
	 * @param callable(TestExecutionResult): mixed $assert
	 */
	public function at(int $index, callable $assert): self
	{
		Assert::assertArrayHasKey($index, $this->data, "Subscription data at index #{$index} wasn't emitted.");

		$assert($this->data[$index]);

		return $this;
	}

	/**
	 * @return list<TestExecutionResult>
	 */
	public function all(): array
	{
		return $this->data;
	}
}
