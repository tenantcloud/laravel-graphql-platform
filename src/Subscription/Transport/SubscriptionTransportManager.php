<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Transport;

use Illuminate\Support\Manager;

class SubscriptionTransportManager extends Manager
{
	public function getDefaultDriver(): ?string
	{
		return null;
	}
}
