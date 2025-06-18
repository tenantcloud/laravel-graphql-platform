<?php

namespace TenantCloud\GraphQLPlatform\Connection\Offset;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of OffsetConnectionEdge<NodeType>
 */
interface OffsetConnection
{
	/**
	 * @return list<NodeType>
	 */
	public function nodes(): array;

	/**
	 * @return list<EdgeType>
	 */
	public function edges(): array;
}
