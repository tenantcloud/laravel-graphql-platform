<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Pagination\CursorPaginator as LaravelCursorPaginator;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionEdge;
use Webmozart\Assert\Assert;

/**
 * @template NodeType
 *
 * @template-implements ConnectionEdge<NodeType>
 */
class CursorPaginatorConnectionEdgeAdapter implements ConnectionEdge
{
	/**
	 * @param CursorPaginator<NodeType> $paginator
	 * @param NodeType                  $item
	 */
	public function __construct(
		public readonly CursorPaginator $paginator,
		public readonly mixed $item,
	) {}

	public function node(): mixed
	{
		return $this->item;
	}

	public function cursor(): string
	{
		// For whatever reason, getCursorForItem() is not part of the interface, so this assert is here.
		Assert::isInstanceOf($this->paginator, LaravelCursorPaginator::class);

		return $this->paginator->getCursorForItem($this->item, false)->encode();
	}
}
