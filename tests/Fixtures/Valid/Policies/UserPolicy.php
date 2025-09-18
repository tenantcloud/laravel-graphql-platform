<?php

namespace Tests\Fixtures\Valid\Policies;

use Illuminate\Auth\Access\Response;
use Tests\Fixtures\Valid\Models\Eloquent\User;

class UserPolicy
{
	public static int $calledTimes = 0;

	public function view(User $auth, User $entity): Response
	{
		self::$calledTimes++;

		return $auth->getKey() === $entity->getKey() ?
			Response::allow() :
			Response::deny();
	}
}
