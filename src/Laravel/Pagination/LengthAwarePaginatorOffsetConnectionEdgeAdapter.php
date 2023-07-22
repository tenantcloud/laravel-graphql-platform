<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;

/**
 * @template NodeType
 *
 * @template-implements OffsetConnectionEdge<NodeType>
 */
class LengthAwarePaginatorOffsetConnectionEdgeAdapter implements OffsetConnectionEdge
{
	/**
	 * @param LengthAwarePaginator<NodeType> $paginator
	 * @param NodeType                       $item
	 */
	public function __construct(
		public readonly LengthAwarePaginator $paginator,
		public readonly mixed $item,
	) {}

	public function node(): mixed
	{
		return $this->item;
	}
}
