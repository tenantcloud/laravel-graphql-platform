<?php

namespace Tests\Fixtures\Valid\Controllers\Eloquent;

use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\Laravel\Database\Transactional;
use Tests\Fixtures\Valid\Models\Eloquent\Blog;
use Tests\Fixtures\Valid\Models\Eloquent\Data\CreateBlogData;
use Tests\Fixtures\Valid\Models\Eloquent\User;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class BlogController
{
	/**
	 * @return OffsetConnectable<Blog>
	 */
	#[Query]
	#[Cost(10, multipliers: ['limit'])]
	#[UseConnections(totalCount: true)]
	public function blogs(): OffsetConnectable
	{
		return Blog::query()
			->orderBy('id')
			->toGraphQLConnectable();
	}

	#[Mutation]
	#[Transactional]
	public function createBlog(
		#[InjectUser] User $auth,
		CreateBlogData $data
	): Blog {
		$blog = new Blog();
		$blog->owner()->associate($auth);
		$blog->name = $data->name;
		$blog->save();

		return $blog;
	}
}
