<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

/**
 * @template-covariant NodeType
 */
interface ConnectionEdge
{
	/**
	 * @return NodeType
	 */
	public function node(): mixed;

	public function cursor(): string;
}
