<?php

namespace TenantCloud\GraphQLPlatform\Context;

use SplObjectStorage;
use Tests\Unit\Context\ContextTest;
use TheCodingMachine\GraphQLite\Context\ContextInterface;
use TheCodingMachine\GraphQLite\Context\ResetableContextInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;
use TheCodingMachine\GraphQLite\PrefetchBuffer;

/**
 * @see ContextTest
 */
final class Context implements ContextInterface, ResetableContextInterface
{
	/** @var SplObjectStorage<object, mixed> */
	private SplObjectStorage $data;

	/** @var SplObjectStorage<ParameterInterface, PrefetchBuffer> */
	private SplObjectStorage $prefetchBuffers;

	public function __construct()
	{
		$this->data = new SplObjectStorage();
		$this->prefetchBuffers = new SplObjectStorage();
	}

	public function has(ContextToken $token): bool
	{
		return isset($this->data[$token]);
	}

	/**
	 * @template T
	 *
	 * @param ContextToken<T> $token
	 *
	 * @return T
	 */
	public function get(ContextToken $token): mixed
	{
		if ($this->has($token)) {
			return $this->data[$token];
		}

		$value = ($token->default)();

		$this->set($token, $value);

		return $value;
	}

	/**
	 * @template T
	 *
	 * @param ContextToken<T> $token
	 * @param T               $value
	 */
	public function set(ContextToken $token, mixed $value): mixed
	{
		return $this->data[$token] = $value;
	}

	public function getPrefetchBuffer(ParameterInterface $field): PrefetchBuffer
	{
		if ($this->prefetchBuffers->offsetExists($field)) {
			$prefetchBuffer = $this->prefetchBuffers->offsetGet($field);
		} else {
			$prefetchBuffer = new PrefetchBuffer();
			$this->prefetchBuffers->offsetSet($field, $prefetchBuffer);
		}

		return $prefetchBuffer;
	}

	public function reset(): void
	{
		$this->data = new SplObjectStorage();
		$this->prefetchBuffers = new SplObjectStorage();
	}
}
