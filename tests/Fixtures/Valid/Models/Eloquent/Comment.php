<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\MagicField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @property int $id
 * @property string $content
 */
#[Type]
#[MagicField(name: 'id', outputType: 'ID!')]
#[MagicField(name: 'content', outputType: 'String!')]
class Comment extends Model
{
	/**
	 * @return BelongsTo<Post, $this>
	 */
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class);
	}

	/**
	 * @return BelongsTo<Comment|null, Comment>
	 * @phpstan-ignore-next-line https://github.com/larastan/larastan/issues/2335
	 */
	#[Field]
	public function parent(): BelongsTo
	{
		/** @phpstan-ignore-next-line https://github.com/larastan/larastan/issues/2335 */
		return $this->belongsTo(self::class);
	}
}
