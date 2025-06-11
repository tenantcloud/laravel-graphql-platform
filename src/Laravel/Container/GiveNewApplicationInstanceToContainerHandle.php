<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Container;

use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;

class GiveNewApplicationInstanceToContainerHandle
{
	public function handle(RequestReceived|TaskReceived|TickReceived $event): void
	{
		if (!$event->sandbox->resolved(GraphQLPlatformServiceProvider::CONTAINER_HANDLE)) {
			return;
		}

		with($event->sandbox->make(GraphQLPlatformServiceProvider::CONTAINER_HANDLE), function (LaravelContainerHandle $handle) use ($event) {
			$handle->setContainer($event->sandbox);
		});
	}
}
