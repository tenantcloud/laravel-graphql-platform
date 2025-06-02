<?php

namespace TenantCloud\GraphQLPlatform\Context;

use SplObjectStorage;
use TheCodingMachine\GraphQLite\Context\ContextInterface;
use TheCodingMachine\GraphQLite\Context\ResetableContextInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;
use TheCodingMachine\GraphQLite\PrefetchBuffer;

final class Context implements ContextInterface, ResetableContextInterface
{
	/** @var SplObjectStorage<object, mixed> */
	private SplObjectStorage $data;

	public function __construct()
	{
		$this->data = new SplObjectStorage();
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
		static $token;

		if (!$token) {
			$token = new ContextToken(fn () => new PrefetchBuffer());
		}

		return $this->get($token);
	}

	public function reset(): void
	{
		$this->data = new SplObjectStorage();
	}
}
