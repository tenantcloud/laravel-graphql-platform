<?php

namespace TenantCloud\GraphQLPlatform\Connection\Cursor;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of CursorConnectionEdge<NodeType>
 */
interface CursorConnectable
{
	/**
	 * @return CursorConnection<NodeType, EdgeType>
	 */
	public function cursor(?int $first, ?string $after, ?int $last, ?string $before): CursorConnection;
}
