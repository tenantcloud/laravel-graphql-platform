<?php

namespace Tests\Fixtures\Controllers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Pagination\UseConnections;
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
			items: [$this->dummyUser()],
			total: 1,
			perPage: $perPage,
		);
	}

	#[Query]
	public function firstUser(): User
	{
		return $this->dummyUser();
	}

	#[Mutation]
	public function createUser(CreateUserData $data): void
	{
	}

	#[Mutation]
	public function updateUser(UpdateUserData $data): User
	{
		$user = $this->dummyUser();

		if ($data->name !== MissingValue::INSTANCE) {
			$user = $user->with(name: $data->name);
		}

		if ($data->somethingAfter !== MissingValue::INSTANCE) {
			$user = $user->with(somethingAfter: $data->somethingAfter);
		}

		return $user->with(fileIds: $data->fileIds);
	}

	private function dummyUser(): User
	{
		return new User(
			name: 'Alex',
			createdAt: CarbonImmutable::create(2020, 1, 3),
			somethingAfter: CarbonInterval::hour(),
		);
	}
}
