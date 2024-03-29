<?php

namespace TenantCloud\GraphQLPlatform\Connection;

use Attribute;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class UseConnections implements MiddlewareAnnotationInterface
{
	/**
	 * @param string|null $prefix         Specify a custom connections prefix, e.g. `UserFriends` for a `UserFriendsConnection`
	 * @param bool        $cursor         Expose cursor-based pagination
	 * @param bool        $offset         Expose offset-based pagination
	 * @param int|null    $limit          Maximum number of items you can request, or the default from the configuration
	 * @param bool        $totalCount     Expose total count of items as `totalCount` field or not
	 * @param string|null $nodeType       Overwrite node type using GraphQL type name
	 * @param string|null $cursorEdgeType Overwrite cursor edge type using GraphQL type name
	 * @param string|null $offsetEdgeType Overwrite offset edge type using GraphQL type name
	 */
	public function __construct(
		public readonly ?string $prefix = null,
		public readonly bool $cursor = false,
		public readonly bool $offset = false,
		public readonly ?int $limit = null,
		public readonly bool $totalCount = false,
		public readonly ?string $nodeType = null,
		public readonly ?string $cursorEdgeType = null,
		public readonly ?string $offsetEdgeType = null,
	) {}
}
