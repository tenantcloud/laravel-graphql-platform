<?php

namespace TenantCloud\GraphQLPlatform\Connection\Cursor;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of CursorConnectionEdge<NodeType>
 */
interface CursorConnection
{
	/**
	 * @return NodeType[]
	 */
	public function nodes(): array;

	/**
	 * @return EdgeType[]
	 */
	public function edges(): array;

	public function pageInfo(): CursorConnectionPageInfo;
}
