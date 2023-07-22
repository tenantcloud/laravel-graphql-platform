<?php

namespace TenantCloud\GraphQLPlatform\Connection;

use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;

/**
 * @template-covariant NodeType
 * @template-covariant CursorConnectionEdgeType of CursorConnectionEdge<NodeType>
 * @template-covariant OffsetConnectionEdgeType of OffsetConnectionEdge<NodeType>
 *
 * @template-extends CursorConnectable<NodeType, CursorConnectionEdgeType>
 * @template-extends OffsetConnectable<NodeType, OffsetConnectionEdgeType>
 */
interface Connectable extends CursorConnectable, OffsetConnectable
{
}
