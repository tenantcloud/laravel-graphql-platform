<?php

namespace Tests\Fixtures\Valid\Controllers;

use TenantCloud\GraphQLPlatform\Connection\Connectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PaginationController
{
	/**
	 * @return OffsetConnectable<Blog>
	 */
	#[Query]
	#[UseConnections(totalCount: true)]
	public function offsetConnectable(): OffsetConnectable
	{
		return Blog::query()->toGraphQLConnectable();
	}

	/**
	 * @return CursorConnectable<Blog>
	 */
	#[Query]
	public function cursorConnectable(): CursorConnectable
	{
		return Blog::query()->toGraphQLConnectable();
	}

	/**
	 * @return Connectable<Blog>
	 */
	#[Query]
	#[UseConnections(
		prefix: 'BlogOther',
		cursor: true,
		offset: true,
	)]
	public function connectable(): Connectable
	{
		return Blog::query()->toGraphQLConnectable();
	}
}
