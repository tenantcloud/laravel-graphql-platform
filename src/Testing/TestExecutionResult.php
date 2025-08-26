<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\Constraints\ArraySubset;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Assert;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionTransportChangedException;

class TestExecutionResult extends ExecutionResult
{
	use Macroable;

	public function __construct(
		?array $data = null,
		array $errors = [],
		array $extensions = [],
		public readonly SubscriptionEmitRecorder $subscriptionEmitRecorder = new SubscriptionEmitRecorder(
		)
	) {
		parent::__construct($data, $errors, $extensions);
	}

	public static function fromExecutionResult(
		ExecutionResult $result,
		SubscriptionEmitRecorder $subscriptionEmitRecorder = new SubscriptionEmitRecorder(),
	): self {
		$errors = $result->errors;

		if (count($errors) === 1 && $errors[0]->getPrevious() instanceof SubscriptionTransportChangedException) {
			$errors = [];
		}

		$testResult = new self(
			$result->data,
			$errors,
			$result->extensions,
			$subscriptionEmitRecorder,
		);

		return $testResult
			->setErrorsHandler(
				(new ReflectionProperty(ExecutionResult::class, 'errorsHandler'))->getValue($result)
			)
			->setErrorFormatter(
				(new ReflectionProperty(ExecutionResult::class, 'errorFormatter'))->getValue($result)
			);
	}

	/**
	 * Assert all fields executed without errors.
	 *
	 * To assert the opposite (failure), just assert for specific errors using {@see self::assertErrors()}
	 */
	public function assertSuccessful(): self
	{
		$this->assertErrors(
			fn (AssertableJson $json) => Assert::assertSame([], $json->toArray(), 'Request was not successful. These errors have occurred:')
		);

		return $this;
	}

	/**
	 * Assert the data of the field when it executed successfully. Use $field when there 2 or more fields.
	 *
	 * @param array<mixed, mixed>|int|float|string|bool|(callable(AssertableJson): mixed)|null $expected
	 * @param string|null                                                                      $field    Optionally specify the field name if there is more than 1
	 *
	 * @return $this
	 */
	public function assertData(callable|array|int|float|string|bool|null $expected, ?string $field = null): self
	{
		$data = $this->data($field);

		if (is_callable($expected)) {
			$assert = AssertableJson::fromArray($data);

			$expected($assert);

			if (Arr::isAssoc($assert->toArray())) {
				$assert->interacted();
			}
		} elseif (is_array($expected)) {
			Assert::assertNotNull($data);
			Assert::assertThat($data, new ArraySubset($expected, true));
		} else {
			Assert::assertSame($expected, $data);
		}

		return $this;
	}

	/**
	 * Assert the errors of the field when it executed unsuccessfully. Use $field when there 2 or more fields.
	 *
	 * @param callable|array<mixed, mixed> $expected
	 */
	public function assertErrors(callable|array $expected): self
	{
		$data = $this->toArray()['errors'] ?? [];

		if (is_callable($expected)) {
			$assert = AssertableJson::fromArray($data);

			$expected($assert);

			if (Arr::isAssoc($assert->toArray())) {
				$assert->interacted();
			}
		} else {
			Assert::assertThat($data, new ArraySubset($expected, true));
		}

		return $this;
	}

	/**
	 * Get the result of a successfully executed field. Use $field when there 2 or more fields.
	 *
	 * @return array<mixed, mixed>|int|float|string|bool|null
	 */
	public function data(?string $field = null): array|int|float|string|bool|null
	{
		if ($field === null) {
			$fieldNames = array_keys($this->data ?? []);

			Assert::assertCount(1, $fieldNames, 'When more than one field result is returned, field name must be specified.');

			$field = $fieldNames[0];
		}

		Assert::assertArrayHasKey($field, $this->data ?? []);

		return $this->data[$field];
	}

	/**
	 * Get the result of an unsuccessfully executed field. Use $field when there 2 or more fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function errors(?string $field = null): array
	{
		if ($field === null) {
			$fieldNames = array_filter(
				array_map(fn (Error $error) => $error->path[0] ?? null, $this->errors),
			);

			Assert::assertCount(1, $fieldNames, 'When more than one field result is returned, field name must be specified.');

			$field = $fieldNames[0];
		}

		$errors = $this->toArray()['errors'] ?? [];

		return array_filter($errors, fn (array $error) => ($error['path'][0] ?? null) === $field);
	}

	public function assertEmittedTimes(int $times): self
	{
		Assert::assertCount($times, $this->emitted());

		return $this;
	}

	/**
	 * @param callable(self): mixed $assert
	 */
	public function assertEmitted(int $index, callable $assert): self
	{
		Assert::assertArrayHasKey($index, $this->emitted(), "Subscription data at index #{$index} wasn't emitted.");

		$assert($this->emitted()[$index]);

		return $this;
	}

	/**
	 * @return list<self>
	 */
	public function emitted(): array
	{
		return $this->subscriptionEmitRecorder->all();
	}

	/**
	 * Dump the content from the response and end the script.
	 *
	 * @codeCoverageIgnore
	 */
	public function dd(): never
	{
		$this->dump();

		exit(1);
	}

	/**
	 * Dump the content from the response.
	 *
	 * @codeCoverageIgnore
	 */
	public function dump(): self
	{
		dump($this->toArray());

		return $this;
	}
}
