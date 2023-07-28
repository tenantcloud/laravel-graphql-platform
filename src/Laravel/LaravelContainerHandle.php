<?php

namespace TenantCloud\GraphQLPlatform\Laravel;

use Illuminate\Contracts\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function class_exists;

class LaravelContainerHandle implements ContainerInterface
{
	public function __construct(
		private Container $container
	) {}

	public function setContainer(Container $container): void
	{
		$this->container = $container;
	}

	public function get(string $id): mixed
	{
		return $this->container->get($id);
	}

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
