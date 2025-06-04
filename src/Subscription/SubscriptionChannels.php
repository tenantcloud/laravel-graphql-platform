<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use Illuminate\Contracts\Auth\Authenticatable;
use Webmozart\Assert\Assert;

class SubscriptionChannels
{
	public static function private(mixed $auth, string $channel): string
	{
		Assert::isInstanceOf($auth, Authenticatable::class, "Private channels are only supported with Laravel's Authenticatable interface.");

		return "auth:{$auth->getAuthIdentifier()}:{$channel}";
	}
}
