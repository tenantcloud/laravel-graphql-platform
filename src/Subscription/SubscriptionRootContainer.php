<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

/**
 * A wrapper class for $root so we can check for this using `instanceof` in the subscription resolver.
 *
 * @template TRoot
 */
readonly class SubscriptionRootContainer
{
	/**
	 * @param TRoot                         $root
	 * @param (callable(TRoot): TRoot)|null $resolve
	 */
	public function __construct(
		public mixed $root,
		public mixed $resolve,
	) {}
}
