<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of ConnectionEdge<NodeType>
 */
interface Connection
{
	/**
	 * @return NodeType[]
	 */
	public function nodes(): array;

	/**
	 * @return EdgeType[]
	 */
	public function edges(): array;

	public function pageInfo(): ConnectionPageInfo;
}
