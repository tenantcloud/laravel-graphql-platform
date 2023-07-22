<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Database\Query\Builder;
use TenantCloud\GraphQLPlatform\Connection\Connectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;

/**
 * @template-covariant NodeType
 *
 * @template-implements Connectable<NodeType, CursorConnectionEdge<NodeType>, OffsetConnectionEdge<NodeType>>
 */
class QueryBuilderConnectable implements Connectable
{
	public function __construct(
		private readonly Builder $query,
	) {}

	public function cursor(?int $first, ?string $after, ?int $last, ?string $before): CursorConnection
	{
		return new CursorPaginatorCursorConnectionAdapter(
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
