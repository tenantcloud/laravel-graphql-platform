<?php

namespace TenantCloud\GraphQLPlatform\Connection\Cursor;

/**
 * @template-covariant NodeType
 * @template-covariant EdgeType of CursorConnectionEdge<NodeType>
 */
interface CursorConnection
{
	/**
	 * @return list<NodeType>
	 */
	public function nodes(): array;

	/**
	 * @return list<EdgeType>
	 */
	public function edges(): array;

	public function pageInfo(): CursorConnectionPageInfo;
}
