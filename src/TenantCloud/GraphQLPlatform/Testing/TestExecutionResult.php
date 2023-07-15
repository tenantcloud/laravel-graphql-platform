<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use DeepCopy\Reflection\ReflectionHelper;
use GraphQL\Executor\ExecutionResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
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

	public function assertSuccessful(): self
	{
		Assert::assertSame([], $this->errors);

		return $this;
	}

	public function assertData(callable|array|int|float|string|bool|null $field, callable|array|int|float|string|bool|null $expected = null): self
	{
		if (func_num_args() === 1) {
			$expected = $field;
			$field = null;
		}

		$data = $this->data($field);

		if (is_callable($expected)) {
			$assert = AssertableJson::fromArray($data);

			$expected($assert);

			if (Arr::isAssoc($assert->toArray())) {
				$assert->interacted();
			}
		} elseif (is_array($expected)) {
			Assert::assertArraySubset($expected, $data, true);
		} else {
			Assert::assertSame($expected, $data);
		}

		return $this;
	}

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

	public function data(string $field = null): array|int|float|string|bool|null
	{
		if ($field === null) {
			$fieldNames = [
				...array_keys($this->data ?? []),
				...array_filter(
					array_map(fn (array $error) => $error['path'][0] ?? null, $this->errors),
				)
			];

			Assert::assertCount(1, $fieldNames, "When more than one field result is returned, field name must be specified.");

			$field = $fieldNames[0];
		}

		Assert::assertArrayHasKey($field, $this->data);

		return $this->data[$field];
	}

	/**
	 * Dump the content from the response and end the script.
	 */
	public function dd(): never
	{
		$this->dump();

		exit(1);
	}

	/**
	 * Dump the content from the response.
	 */
	public function dump(): self
	{
		dump([
			'data' => $this->data,
			'errors' => $this->errors,
			'extensions' => $this->extensions,
		]);

		return $this;
	}
}
