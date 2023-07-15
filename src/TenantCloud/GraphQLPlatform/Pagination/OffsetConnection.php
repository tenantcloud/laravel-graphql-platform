<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

use TheCodingMachine\GraphQLite\Annotations\Field;

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
