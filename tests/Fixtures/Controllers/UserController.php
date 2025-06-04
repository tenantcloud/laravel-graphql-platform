<?php

namespace Tests\Fixtures\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use Tests\Fixtures\Models\CreateUserData;
use Tests\Fixtures\Models\UpdateUserData;
use Tests\Fixtures\Models\User;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Subscription;

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
	public function createUser(CreateUserData $data): User
	{
		return new User(
			name: $data->name,
			createdAt: $data->createdAt,
			somethingAfter: $data->somethingAfter,
		);
	}

	#[Mutation]
	public function updateUser(
		UpdateUserData $data,
		ValidationExceptions $validationExceptions,
	): User {
		if (is_array($data->nest) && $data->nest && $data->nest[0]->name === 'bobo') {
			throw $validationExceptions->forProperty('data', $data, 'nest[0].name', ['Name is bobo - dont you see?']);
		}

		$user = User::dummy();

		if ($data->name !== MissingValue::INSTANCE) {
			$user = $user->with(name: $data->name);
		}

		if ($data->somethingAfter !== MissingValue::INSTANCE) {
			$user = $user->with(somethingAfter: $data->somethingAfter);
		}

		return $user->with(fileIds: $data->fileIds);
	}

	/**
	 * @return ChannelSubscription<User>
	 */
	#[Subscription]
	public function newUser(): ChannelSubscription
	{
		return new ChannelSubscription(
			'users.new',
		);
	}
}
