<?php

namespace TenantCloud\GraphQLPlatform\Connection\Offset;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of OffsetConnectionEdge<NodeType>
 */
interface OffsetConnectable
{
	/**
	 * @return OffsetConnection<NodeType, EdgeType>
	 */
	public function offset(int $limit, int $offset): OffsetConnection;
}
