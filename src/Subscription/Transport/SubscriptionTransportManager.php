<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Transport;

use Illuminate\Support\Manager;
use Tests\Fixtures\BroadcastSubscriptionTransport;

class SubscriptionTransportManager extends Manager
{
	public function getDefaultDriver(): ?string
	{
		return null;
	}
}
