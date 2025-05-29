<?php

namespace TenantCloud\GraphQLPlatform\Context;

use Closure;

/**
 * @template-covariant T
 */
final class ContextToken
{
	/**
	 * @param Closure(): T $default
	 */
	public function __construct(
		public readonly Closure $default,
	)
	{
	}
}
