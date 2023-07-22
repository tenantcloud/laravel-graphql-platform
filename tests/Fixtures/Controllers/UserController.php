<?php

namespace Tests\Fixtures\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\QueryComplexity\Cost;
use Tests\Fixtures\Models\CreateUserData;
use Tests\Fixtures\Models\UpdateUserData;
use Tests\Fixtures\Models\User;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class UserController
{
	/**
	 * @return LengthAwarePaginator<User>
	 */
	#[Query]
	#[Cost(10, multipliers: ['perPage'])]
	#[UseConnections(totalCount: true)]
	public function listUsers(int $perPage = 15): LengthAwarePaginator
	{
		return new LengthAwarePaginatorImpl(
			items: [User::dummy()],
			total: 1,
			perPage: $perPage,
		);
	}

	#[Query]
	public function firstUser(): User
	{
		return User::dummy();
	}

	#[Mutation]
	public function createUser(CreateUserData $data): void
	{
	}

	#[Mutation]
	public function updateUser(UpdateUserData $data): User
	{
		$user = User::dummy();

		if ($data->name !== MissingValue::INSTANCE) {
			$user = $user->with(name: $data->name);
		}

		if ($data->somethingAfter !== MissingValue::INSTANCE) {
			$user = $user->with(somethingAfter: $data->somethingAfter);
		}

		return $user->with(fileIds: $data->fileIds);
	}
}
