<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Database\Query\Builder;
use TenantCloud\GraphQLPlatform\Pagination\Connectable;
use TenantCloud\GraphQLPlatform\Pagination\Connection;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnection;

class QueryBuilderConnectable implements Connectable
{
	public function __construct(
		private readonly Builder $query,
	) {}

	public function cursor(?int $first, ?string $after, ?int $last, ?string $before): Connection
	{
		return new CursorPaginatorConnectionAdapter(
			$this->query->cursorPaginate(perPage: $perPage, cursor: $cursor)
		);
	}

	public function offset(int $limit, int $offset): OffsetConnection
	{
		$page = (int) floor($offset / $limit) + 1;

		return new LengthAwarePaginatorOffsetConnectionAdapter(
			$this->query->paginate(perPage: $limit, page: $page)
		);
	}
}
