<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Transport;

use Illuminate\Support\Manager;

class SubscriptionTransportManager extends Manager
{
	/**
	 * @codeCoverageIgnore
	 */
	public function getDefaultDriver(): ?string
	{
		return null;
	}
}
