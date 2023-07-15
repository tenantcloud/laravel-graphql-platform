<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth;

use Illuminate\Contracts\Auth\Access\Gate;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class LaravelAuthorizationService implements AuthorizationServiceInterface
{
	public function __construct(
		private readonly Gate $gate,
		private readonly AuthenticationServiceInterface $authenticationService
	) {
	}

	/**
	 * Returns true if the "current" user has access to the right "$right"
	 *
	 * @param mixed $subject The scope this right applies on. $subject is typically an object or a FQCN. Set $subject to "null" if the right is global.
	 */
	public function isAllowed(string $right, $subject = null): bool
	{
		return $this->gate->forUser($this->authenticationService->getUser())->check($right, $subject);
	}
}
