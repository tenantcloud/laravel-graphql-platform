<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\ProvidesTotalCount;

/**
 * @template NodeType
 *
 * @template-implements OffsetConnection<NodeType, OffsetConnectionEdge<NodeType>>
 */
class LengthAwarePaginatorOffsetConnectionAdapter implements OffsetConnection, ProvidesTotalCount
{
	/**
	 * @param LengthAwarePaginator<NodeType> $paginator
	 */
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
