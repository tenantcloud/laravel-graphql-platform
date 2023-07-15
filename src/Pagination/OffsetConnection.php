<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of OffsetConnectionEdge<NodeType>
 */
interface OffsetConnection
{
	/**
	 * @return NodeType[]
	 */
	public function nodes(): array;

	/**
	 * @return EdgeType[]
	 */
	public function edges(): array;
}
