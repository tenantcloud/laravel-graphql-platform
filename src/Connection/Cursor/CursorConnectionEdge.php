<?php

namespace TenantCloud\GraphQLPlatform\Connection\Cursor;

/**
 * @template-covariant NodeType
 */
interface CursorConnectionEdge
{
	/**
	 * @return NodeType
	 */
	public function node(): mixed;

	public function cursor(): string;
}
