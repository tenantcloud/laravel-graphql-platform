<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;
use TenantCloud\GraphQLPlatform\Pagination\Connection;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionPageInfo;

class CursorPaginatorConnectionAdapter implements Connection
{
	public function __construct(
		public readonly CursorPaginator $paginator,
	)
	{
	}

	public function nodes(): array
	{
		return $this->paginator->items();
	}

	public function edges(): array
	{
		return array_map(
			fn (mixed $item) => new CursorPaginatorConnectionEdgeAdapter($this->paginator, $item),
			$this->paginator->items()
		);
	}

	public function pageInfo(): ConnectionPageInfo
	{
		return ConnectionPageInfo::fromCursors(
			startCursor: $this->paginator->previousCursor()?->encode(),
			endCursor: $this->paginator->nextCursor()?->encode(),
		);
	}
}
