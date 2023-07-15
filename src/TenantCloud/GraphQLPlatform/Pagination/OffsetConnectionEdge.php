<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

/**
 * @template-covariant NodeType
 */
interface OffsetConnectionEdge
{
	/**
	 * @return NodeType
	 */
	public function node(): mixed;
}
