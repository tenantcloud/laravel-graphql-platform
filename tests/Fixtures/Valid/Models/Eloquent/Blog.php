<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\EloquentBatchLoader;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;
use TheCodingMachine\GraphQLite\Annotations\Autowire;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\MagicField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @property int    $id
 * @property string $name
 */
#[Type]
#[MagicField(name: 'id', outputType: 'ID!')]
#[MagicField(name: 'name', outputType: 'String!', annotations: [new Cost(3)])]
class Blog extends Model
{
	/**
	 * @return HasMany<Post, $this>
	 */
	#[Field]
	public function posts(): HasMany
	{
		return $this->hasMany(Post::class);
	}

	/**
	 * @return Closure(): int
	 */
	#[Field]
	public function postsCount(
		ResolveKey $resolveKey,
		#[Autowire] EloquentBatchLoader $eloquentBatchLoader,
		?string $search = null,
	): Closure {
		return $eloquentBatchLoader->deferCount(
			$resolveKey,
			$this,
			'posts',
			fn (Builder $query) => $query
				->when($search !== null, fn (Builder $query) => $query->where('content', 'like', "%{$search}%"))
		);
	}
}
