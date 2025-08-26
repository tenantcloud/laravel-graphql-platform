<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TenantCloud\GraphQLPlatform\Connection\Connectable;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\EloquentBatchLoader;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;
use Tests\Fixtures\Valid\Models\Eloquent\Data\CommentsData;
use TheCodingMachine\GraphQLite\Annotations\Autowire;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\MagicField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @property int                 $id
 * @property string              $content
 * @property CarbonInterval|null $read_time
 */
#[Type]
#[MagicField(name: 'id', outputType: 'ID!')]
#[MagicField(name: 'content', outputType: 'String!')]
#[MagicField(name: 'readTime', outputType: 'Duration', sourceName: 'read_time')]
class Post extends Model
{
	/**
	 * @return Attribute<CarbonInterval|null, CarbonInterval|null>
	 */
	public function readTime(): Attribute
	{
		return new Attribute(
			get: fn (?string $value) => $value ? CarbonInterval::fromString($value) : null,
			set: fn (?CarbonInterval $value) => $value?->spec(),
		);
	}

	/**
	 * @return BelongsTo<Blog, $this>
	 */
	public function blog(): BelongsTo
	{
		return $this->belongsTo(Blog::class);
	}

	/**
	 * @return HasMany<Comment, $this>
	 */
	public function comments(): HasMany
	{
		return $this->hasMany(Comment::class);
	}

	/**
	 * @return \Closure(): int
	 */
	#[Field]
	public function commentsCount(
		ResolveKey $resolveKey,
		#[Autowire] EloquentBatchLoader $eloquentBatchLoader,
	): \Closure
	{
		return $eloquentBatchLoader->deferCount($resolveKey, $this, 'comments');
	}

	/**
	 * @return Connectable<Comment>
	 */
	#[Field(name: 'comments')]
	public function commentsField(?CommentsData $data = null, ?Comment $parent = null): Connectable
	{
		return $this->comments()
			->when($data?->minRating !== null, fn (Builder $query) => $query->where('rating', '>=', $data->minRating))
			->when($parent !== null, fn (Builder $query) => $query->whereBelongsTo($parent, 'parent'))
			->toGraphQLConnectable();
	}
}
