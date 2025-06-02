<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use Illuminate\Contracts\Auth\Authenticatable;

class SubscriptionChannels
{
	public static function private(Authenticatable $auth, string $channel): string
	{
		return "auth:{$auth->getAuthIdentifier()}:$channel";
	}
}
