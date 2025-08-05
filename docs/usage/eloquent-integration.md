# Eloquent integration

There are some things built specifically for Laravel Eloquent models to make it 
easier to eager load relations, counts and other aggregations in as few SQL
queries as possible - the same way you would in a regular API.

### Relation fields

One of the things that's supported is marking a relationship method as `#[Field]` -
usually that wouldn't work, as GraphQLite would simply call the relationship method
and try to serialize the `Relation` instance as a GraphQL type. Instead, whenever
a `Relation` return type is found, it automatically sets up eager loading for it
and returns the actual result (model or collection) instead of the `Relation` instance:

```php
#[Type]
class Post {
	/** @return HasMany<Comment> */
	#[Field]
	public function comments(): HasMany {
    	return $this->hasMany(Comment::class);
	}
}
```

Note that the generic return type (`HasMany<Comment>`) is required for this to work.
If a client then requests multiple posts (for example, from a paginated list), alongside
with their comments, instead of making multiple `select * from comments where post_id = ?`
queries for every post, it would actually make a single query:
`select * from comments post_id in (?, ?, ?)`

### EloquentBatchLoader

Now this is what powers the automatic loading of relations under the hood, and allows you
to do much more complex loads or aggregations. What it is a singleton object that
allows batching calls on a query builder (like `->with()` or `->withCount()`) and
returning results from those calls automatically by utilizing 
[GraphQLite's field deferring](https://graphqlite.thecodingmachine.io/docs/type-mapping#promise-mapping).

Here's how it could look:

```php
#[Input]
class Post {
	public function comments(): HasMany {
    	return $this->hasMany(Comment::class);
	}

	/**
	 * @return Closure(): int
	 */
	#[Field]
	public function commentsCount(
		ResolveKey $resolveKey,
		#[Autowire] EloquentBatchLoader $eloquentBatchLoader,
	): Closure {
		return $eloquentBatchLoader->deferCount($resolveKey, $this, 'comments');
	}
}
```

Looks a bit clunky. Let's decompose:
- `@return Closure(): int` is what tells GraphQLite that the field type is `int` - because
we're returning a count of a relation. However, it can be any other type, the same way
it could in a regular return type: `@return Closure(): OtherModel`
- `ResolveKey $resolveKey` is a unique key for that specific field. It's necessary for
`EloquentBatchLoader` not to mix stuff in more complex scenarios we'll talk about later
- `#[Autowire] EloquentBatchLoader $eloquentBatchLoader` simply injects the dependency we need
- `$eloquentBatchLoader->deferCount($resolveKey, $this, 'comments');` basically tells
the `EloquentBatchLoader` "hey, record this model ($this) for future reference. When asked, 
load the 'comments' relation on all models of this type, and return the result"

How it works under the hood is basically this:

```php
#[Input]
class Post {
	private static $modelsToLoadCommentsCount = [];
	private static $modelsToLoadCommentsCountResults = null;

	public function comments(): HasMany {
    	return $this->hasMany(Comment::class);
	}

	/**
	 * @return Closure(): int
	 */
	#[Field]
	public function commentsCount(): Closure {
		// If there are three posts (our model), this will first get executed three times
		self::$modelsToLoadCommentsCount[] = $this->id;
		
		return function () {
			// And only after the above lines was already executed for ALL three posts
			// will this get executed. At this point we know all models that requested
			// commentsCount field, so we'll check if it's been loaded already:
			if (self::$modelsToLoadCommentsCountResults === null) {
				self::$modelsToLoadCommentsCountResults = $this
					->newQuery()
					->whereKey(self::$modelsToLoadCommentsCount)
					->withCount('comments')
					->get();
			}
			
			// Once the counts were loaded for all models, we can just
			// find the result we wanted in the first place
			return self::$modelsToLoadCommentsCountResults
				->find($this->id)
				->comments_count;
		};
	}
}
```

The `EloquentBatchLoader` does more or less the same thing, but with even more optimizations.
It also allows field arguments:

```php
#[Input]
class Post {
	public function comments(): HasMany {
    	return $this->hasMany(Comment::class);
	}

	/**
	 * @return Closure(): int
	 */
	#[Field]
	public function commentsCount(
		ResolveKey $resolveKey,
		#[Autowire] EloquentBatchLoader $eloquentBatchLoader,
		?string $search = null,
	): Closure {
		return $eloquentBatchLoader->deferCount(
			$resolveKey,
			$this,
			'comments',
			fn (Builder $query) => $query
				->when($search !== null, fn (Builder $query) => $query->where('content', 'like', "%{$search}%"))
		);
	}
}
```

and you can do your own method calls and transformations:

```php
#[Input]
class Post {
	public function comments(): HasMany {
    	return $this->hasMany(Comment::class);
	}

	/**
	 * @return Closure(): float
	 */
	#[Field]
	public function commentsCount(
		ResolveKey $resolveKey,
		#[Autowire] EloquentBatchLoader $eloquentBatchLoader,
	): Closure {
		return $eloquentBatchLoader->defer(
			$resolveKey,
			$this,
			fn (Builder $query) => $query
				->with('comments'),
			fn (Model $model) => ($model->comments->count() / 2) * $model->modifier,
		);
	}
}
```
