<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionPageInfo;

/**
 * @template NodeType
 *
 * @template-implements CursorConnection<NodeType, CursorConnectionEdge<NodeType>>
 */
class CursorPaginatorCursorConnectionAdapter implements CursorConnection
{
	/**
	 * @param CursorPaginator<NodeType> $paginator
	 */
	public function __construct(
		public readonly CursorPaginator $paginator,
	) {}

	public function nodes(): array
	{
		return $this->paginator->items();
	}

	public function edges(): array
	{
		return array_map(
			fn (mixed $item) => new CursorPaginatorCursorConnectionEdgeAdapter($this->paginator, $item),
			$this->paginator->items()
		);
	}

	public function pageInfo(): CursorConnectionPageInfo
	{
		return CursorConnectionPageInfo::fromCursors(
			startCursor: $this->paginator->previousCursor()?->encode(),
			endCursor: $this->paginator->nextCursor()?->encode(),
		);
	}
}
