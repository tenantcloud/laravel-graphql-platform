<?php

namespace Tests\Fixtures\Valid\Controllers;

use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LengthAwarePaginatorOffsetConnectionAdapter;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use Tests\Fixtures\Valid\Models\CreateUserData;
use Tests\Fixtures\Valid\Models\UpdateUserData;
use Tests\Fixtures\Valid\Models\User;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Subscription;

class UserController
{
	/**
	 * @return OffsetConnectable<User>
	 */
	#[Query]
	#[Cost(10, multipliers: ['limit'])]
	#[UseConnections(totalCount: true)]
	public function listUsers(): OffsetConnectable
	{
		return new class () implements OffsetConnectable {
			public function offset(int $limit, int $offset): OffsetConnection
			{
				return new LengthAwarePaginatorOffsetConnectionAdapter(
					new LengthAwarePaginatorImpl(
						items: [User::dummy()],
						total: 1,
						perPage: $limit,
					)
				);
			}
		};
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
