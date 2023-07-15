<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionEdge;

class CursorPaginatorConnectionEdgeAdapter implements ConnectionEdge
{
	public function __construct(
		public readonly CursorPaginator $paginator,
		public readonly mixed $item,
	) {}

	public function node(): mixed
	{
		return $this->item;
	}

	public function cursor(): string
	{
		return $this->paginator->getCursorForItem($this->item, false)->encode();
	}
}
