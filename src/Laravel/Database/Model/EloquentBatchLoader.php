<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;

class EloquentBatchLoader
{
	/** @var array<class-string<Model>, array<string, array{ ids: Collection<int, string|int>, apply: callable(Builder): Builder }>> */
	private array $defers = [];

	/** @var array<class-string<Model>, array<string, array<string|int, Model>>> */
	private array $loaded;

	/**
	 * @template TModel of Model
	 * @template TReturn
	 *
	 * @param callable(Builder): Builder $apply
	 * @param callable(TModel): TReturn  $map
	 *
	 * @return Closure(): TReturn
	 */
	public function defer(
		mixed $key,
		Model $model,
		callable $apply,
		callable $map,
	): Closure {
		if (isset($this->loaded)) {
			throw new RuntimeException('Data for this loader has already been loaded');
		}

		// Not developer friendly, but at least it's secure - in a sense that we don't have to escape any
		// of the key parts, join arrays, serialize objects separately etc.
		$key = self::keyHash($key);

		$modelClass = $model::class;
		$modelKey = $model->getKey();

		$this->defers[$modelClass] ??= [];
		$this->defers[$modelClass][$key] ??= [
			'ids'   => new Collection(),
			'apply' => $apply,
		];
		$this->defers[$modelClass][$key]['ids']->push($modelKey);

		return function () use ($modelClass, $key, $modelKey, $map) {
			$entity = $this->loaded($key, $modelClass, $modelKey);

			return $map($entity);
		};
	}

	/**
	 * @return Closure(): int
	 */
	public function deferCount(
		mixed $key,
		Model $model,
		string $relation,
		?callable $callback = null
	): Closure {
		$alias = "{$relation}_count";

		if ($callback !== null) {
			$alias .= '_' . self::keyHash($key);
		}

		return $this->defer(
			$key,
			$model,
			fn (Builder $query) => $callback ?
				$query->withCount([
					"{$relation} as {$alias}" => $callback,
				]) :
				$query->withCount("{$relation} as {$alias}"),
			fn (Model $model) => (int) $model->{$alias}
		);
	}

	/**
	 * @return Closure(): EloquentCollection<int, Model>
	 */
	public function deferWith(
		mixed $key,
		Model $model,
		string $relation,
		?callable $callback = null
	): Closure {
		return $this->defer(
			$key,
			$model,
			fn (Builder $query) => $callback ?
				$query->with([
					$relation => $callback,
				]) :
				$query->with($relation),
			fn (Model $model) => $model->{$relation}
		);
	}

	/**
	 * @param class-string<Model> $modelClass
	 */
	private function loaded(string $key, string $modelClass, string|int $modelKey): Model
	{
		if ($entity = $this->loaded[$modelClass][$key][$modelKey] ?? null) {
			return $entity;
		}

		$this->loadDeferred($key, $modelClass);

		return $this->loaded[$modelClass][$key][$modelKey];
	}

	/**
	 * @param class-string<Model> $modelClass
	 */
	private function loadDeferred(string $key, string $modelClass): void
	{
		// Get the original IDs and apply function, so we can create
		['ids' => $ids, 'apply' => $apply] = $this->defers[$modelClass][$key];

		$query = $this->newQueryForIds($modelClass, $ids->unique()->all());

		$similarDefers = $this->findDeferredForSameModels($modelClass, $ids->all());

		foreach ([$apply, ...array_values($similarDefers)] as $callback) {
			$query = $callback($query);
		}

		/** @var Builder $query */
		$loaded = $query->get()->getDictionary();

		// Copy the same array for all the keys that used the same IDs
		foreach ([$key, ...array_keys($similarDefers)] as $key) {
			$this->loaded[$modelClass][$key] = $loaded;

			unset($this->defers[$modelClass][$key]);
		}
	}

	/**
	 * @param array<int, string|int> $ids
	 *
	 * @return array<string, callable(Builder): Builder>
	 */
	private function findDeferredForSameModels(string $modelClass, array $ids): array
	{
		$result = [];

		foreach ($this->defers[$modelClass] as $key => ['ids' => $candidateIds, 'apply' => $apply]) {
			if ($ids !== $candidateIds->all()) {
				continue;
			}

			$result[$key] = $apply;
		}

		return $result;
	}

	/**
	 * @param class-string<Model>    $modelClass
	 * @param array<int, string|int> $ids
	 */
	private function newQueryForIds(string $modelClass, array $ids): Builder
	{
		$modelPrototype = new $modelClass();

		return $modelPrototype
			->newModelQuery()
			->whereKey($ids);
	}

	private static function keyHash(mixed $key): string
	{
		return $key instanceof ResolveKey ? $key->hash() : md5(serialize($key));
	}
}
