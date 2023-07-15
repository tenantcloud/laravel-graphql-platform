<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnection;
use TenantCloud\GraphQLPlatform\Pagination\ProvidesTotalCount;

class LengthAwarePaginatorOffsetConnectionAdapter implements OffsetConnection, ProvidesTotalCount
{
	public function __construct(
		public readonly LengthAwarePaginator $paginator,
	) {}

	public function nodes(): array
	{
		return $this->paginator->items();
	}

	public function edges(): array
	{
		return array_map(
			fn (mixed $item) => new LengthAwarePaginatorOffsetConnectionEdgeAdapter($this->paginator, $item),
			$this->paginator->items()
		);
	}

	public function totalCount(): int
	{
		return $this->paginator->total();
	}
}
