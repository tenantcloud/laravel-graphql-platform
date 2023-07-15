<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Pagination\Connection;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionPageInfo;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnection;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnectionEdge;
use TenantCloud\GraphQLPlatform\Pagination\ProvidesTotalCount;

class LengthAwarePaginatorOffsetConnectionEdgeAdapter implements OffsetConnectionEdge
{
	public function __construct(
		public readonly LengthAwarePaginator $paginator,
		public readonly mixed $item,
	) {
	}

	public function node(): mixed
	{
		return $this->item;
	}
}
