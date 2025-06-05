<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Illuminate\Support\Arr;
use Illuminate\Testing\Assert;
use Illuminate\Testing\Fluent\AssertableJson;
use ReflectionProperty;

class TestExecutionResult extends ExecutionResult
{
	public function __construct(?array $data = null, array $errors = [], array $extensions = [])
	{
		parent::__construct($data, $errors, $extensions);
	}

	public static function fromExecutionResult(ExecutionResult $result): self
	{
		$testResult = new self(
			$result->data,
			$result->errors,
			$result->extensions,
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
			Assert::assertArraySubset($expected, $data, true);
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
			Assert::assertArraySubset($expected, $data, true);
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
