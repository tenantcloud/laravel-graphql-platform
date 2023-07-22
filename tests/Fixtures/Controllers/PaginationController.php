<?php

namespace Tests\Fixtures\Controllers;

use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Connection\Connectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\CursorPaginatorCursorConnectionAdapter;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LengthAwarePaginatorOffsetConnectionAdapter;
use Tests\Fixtures\Models\User;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PaginationController
{
	/**
	 * @return OffsetConnectable<User>
	 */
	#[Query]
	#[UseConnections(totalCount: true)]
	public function offsetConnectable(): OffsetConnectable
	{
		return new class () implements OffsetConnectable {
			public function offset(int $limit, int $offset): OffsetConnection
			{
				return new LengthAwarePaginatorOffsetConnectionAdapter(
					new LengthAwarePaginator(
						items: [User::dummy()],
						total: 1,
						perPage: $limit,
					)
				);
			}
		};
	}

	/**
	 * @return CursorConnectable<User>
	 */
	#[Query]
	public function cursorConnectable(): CursorConnectable
	{
		return new class () implements CursorConnectable {
			public function cursor(?int $first, ?string $after, ?int $last, ?string $before): CursorConnection
			{
				return new CursorPaginatorCursorConnectionAdapter(
					new CursorPaginator(
						items: [User::dummy()],
						perPage: $first ?? 15,
					)
				);
			}
		};
	}

	/**
	 * @return Connectable<User>
	 */
	#[Query]
	#[UseConnections(
		prefix: 'UserFriends',
		cursor: true,
		offset: true,
	)]
	public function connectable(): Connectable
	{
		return new class () implements Connectable {
			public function offset(int $limit, int $offset): OffsetConnection
			{
				return new LengthAwarePaginatorOffsetConnectionAdapter(
					new LengthAwarePaginator(
						items: [User::dummy()],
						total: 1,
						perPage: $limit,
					)
				);
			}

			public function cursor(?int $first, ?string $after, ?int $last, ?string $before): CursorConnection
			{
				return new CursorPaginatorCursorConnectionAdapter(
					new CursorPaginator(
						items: [User::dummy()],
						perPage: $first ?? 15,
					)
				);
			}
		};
	}
}
