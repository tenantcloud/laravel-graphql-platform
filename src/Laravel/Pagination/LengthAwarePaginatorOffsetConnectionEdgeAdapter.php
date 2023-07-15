<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnectionEdge;

class LengthAwarePaginatorOffsetConnectionEdgeAdapter implements OffsetConnectionEdge
{
	public function __construct(
		public readonly LengthAwarePaginator $paginator,
		public readonly mixed $item,
	) {}

	public function node(): mixed
	{
		return $this->item;
	}
}
