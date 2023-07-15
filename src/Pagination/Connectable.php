<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

/**
 * @template-covariant NodeType
 * @template-covariant ConnectionEdgeType of ConnectionEdge<NodeType>
 * @template-covariant OffsetConnectionEdgeType of OffsetConnectionEdge<NodeType>
 */
interface Connectable
{
	/**
	 * @return Connection<NodeType, ConnectionEdgeType>
	 */
	public function cursor(?int $first, ?string $after, ?int $last, ?string $before): Connection;

	/**
	 * @return OffsetConnection<NodeType, OffsetConnectionEdgeType>
	 */
	public function offset(int $limit, int $offset): OffsetConnection;
}
