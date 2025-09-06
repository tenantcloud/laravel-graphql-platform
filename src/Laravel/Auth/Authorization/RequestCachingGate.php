<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

final class RequestCachingGate implements Gate
{
	/** @var array<string, Response> */
	private array $cache = [];

	public function __construct(
		private readonly Gate $delegate,
	) {}

	public function has($ability): bool
	{
		return $this->delegate->has($ability);
	}

	public function define($ability, $callback): static
	{
		$this->delegate->define($ability, $callback);

		return $this;
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function resource($name, $class, ?array $abilities = null): static
	{
		$this->delegate->resource($name, $class, $abilities);

		return $this;
	}

	public function policy($class, $policy): static
	{
		$this->delegate->policy($class, $policy);

		return $this;
	}

	public function before(callable $callback): static
	{
		$this->delegate->before($callback);

		return $this;
	}

	public function after(callable $callback): static
	{
		$this->delegate->before($callback);

		return $this;
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function allows($ability, $arguments = []): bool
	{
		return $this->check($ability, $arguments);
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function denies($ability, $arguments = []): bool
	{
		return !$this->allows($ability, $arguments);
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function check($abilities, $arguments = []): bool
	{
		return (new Collection($abilities))->every(
			fn ($ability) => $this->inspect($ability, $arguments)->allowed()
		);
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function any($abilities, $arguments = []): bool
	{
		return (new Collection($abilities))->contains(fn ($ability) => $this->check($ability, $arguments));
	}

	public function authorize($ability, $arguments = []): Response
	{
		return $this->inspect($ability, $arguments)->authorize();
	}

	public function inspect($ability, $arguments = []): Response
	{
		$cacheKeyParts = array_map(function (mixed $argument) {
			$key = is_object($argument) ? $argument::class : gettype($argument);

			if ($argument instanceof Model) {
				$key .= ":{$argument->getKey()}";
			} else {
				$key .= ":{$argument}";
			}

			return $key;
		}, [$ability, ...$arguments]);
		$cacheKey = json_encode($cacheKeyParts);

		return $this->cache[$cacheKey] ??= $this->delegate->inspect($ability, $arguments);
	}

	public function raw($ability, $arguments = []): mixed
	{
		throw new RuntimeException('Not implemented. Use inspect() as the closest alternative.');
	}

	public function getPolicyFor($class): mixed
	{
		return $this->delegate->getPolicyFor($class);
	}

	public function forUser($user): self
	{
		return new self(
			$this->delegate->forUser($user)
		);
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	public function abilities(): array
	{
		return $this->delegate->abilities();
	}
}
