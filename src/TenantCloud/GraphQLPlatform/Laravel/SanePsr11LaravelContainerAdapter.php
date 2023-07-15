<?php

namespace TenantCloud\GraphQLPlatform\Laravel;

use function class_exists;
use Illuminate\Contracts\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A container adapter around Laravel containers that adds a "sane" implementation of PSR-11.
 * Notably, "has" will return true if the class exists, since Laravel is an auto-wiring framework.
 */
class SanePsr11LaravelContainerAdapter implements ContainerInterface
{
	public function __construct(
		private readonly Container $container
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $id): mixed
	{
		return $this->container->get($id);
	}

	/**
	 *  @inheritDoc
	 */
	public function has(string $id): bool
	{
		if (class_exists($id) && !$this->container->has($id)) {
			try {
				$this->container->get($id);
			} catch (NotFoundExceptionInterface) {
				return false;
			}

			return true;
		}

		return $this->container->has($id);
	}
}
