<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth;

use Illuminate\Support\Facades\Auth;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

class LaravelAuthenticationService implements AuthenticationServiceInterface
{
	/**
	 * @param string[] $guards
	 */
	public function __construct(
		private readonly array $guards
	) {
	}

	/**
	 * Returns true if the "current" user is logged
	 */
	public function isLogged(): bool
	{
		foreach ($this->guards as $guard) {
			if (Auth::guard($guard)->check()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns an object representing the current logged user.
	 * Can return null if the user is not logged.
	 */
	public function getUser(): ?object
	{
		foreach ($this->guards as $guard) {
			$user = Auth::guard($guard)->user();

			if ($user !== null) {
				return $user;
			}
		}

		return null;
	}
}
