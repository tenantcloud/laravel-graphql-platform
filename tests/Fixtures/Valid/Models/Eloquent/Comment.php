<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\MagicField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @property int    $id
 * @property string $content
 */
#[Type]
#[MagicField(name: 'id', outputType: 'ID!')]
#[MagicField(name: 'content', outputType: 'String!')]
class Comment extends Model
{
	/**
	 * @return BelongsTo<Post, Comment>
	 */
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class);
	}

	/**
	 * @return BelongsTo<Comment, Comment>
	 */
	#[Field]
	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class);
	}
}
