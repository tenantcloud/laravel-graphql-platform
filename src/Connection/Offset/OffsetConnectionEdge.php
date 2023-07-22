<?php

namespace TenantCloud\GraphQLPlatform\Connection\Offset;

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
