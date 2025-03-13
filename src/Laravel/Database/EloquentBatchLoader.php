<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database;

use GraphQL\Deferred;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

class EloquentBatchLoader
{
	/** @var array<class-string<Model>, array<string, array{ ids: Collection<int, string|int>, apply: callable(Builder): Builder }>> */
	private array $defers = [];

	private array $loaded;

	/**
	 * @template TModel of Model
	 * @template TReturn
	 *
	 * @param callable(Builder): Builder $apply
	 * @param callable(TModel): TReturn  $map
	 *
	 * @return Deferred<TReturn>
	 */
	public function defer(
		mixed $key,
		Model $model,
		callable $apply,
		callable $map,
	): Deferred {
		if (isset($this->loaded)) {
			throw new RuntimeException('Data for this loader has already been loaded');
		}

		// Not developer friendly, but at least it's secure - in a sense that we don't have to escape any
		// of the key parts, join arrays, serialize objects separately etc.
		$key = md5(serialize($key));
		$modelClass = $model::class;
		$modelKey = $model->getKey();

		$this->defers[$modelClass] ??= [];
		$this->defers[$modelClass][$key] ??= [
			'ids'   => new Collection(),
			'apply' => $apply,
		];
		$this->defers[$modelClass][$key]['ids']->push($modelKey);

		return new Deferred(function () use ($modelClass, $key, $modelKey, $map) {
			$entity = $this->loaded($key, $modelClass, $modelKey);

			return $map($entity);
		});
	}

	/**
	 * @return Deferred<int>
	 */
	public function deferCount(Model $model, string $relation, callable $callback = null): Deferred
	{
		return $this->defer(
			null,
			$model,
			fn (Builder $query) => $callback ?
				$query->withCount([
					$relation => $callback,
				]) :
				$query->withCount($relation),
			fn (Model $model) => (int) $model->{"{$relation}_count"}
		);
	}

	/**
	 * @return Deferred<int>
	 */
	public function deferWith(Model $model, string $relation, callable $callback = null): Deferred
	{
		return $this->defer(
			null,
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

		$query = $this->newQueryForIds($modelClass, $ids->all());

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
	 * @return array<string, callable(Builder): Builder>
	 */
	private function findDeferredForSameModels(string $modelClass, array $ids): array
	{
		$result = [];

		foreach ($this->defers[$modelClass] as $key => ['ids' => $candidateIds, 'apply' => $apply]) {
			if ($ids !== $candidateIds) {
				continue;
			}

			$result[$key] = $apply;
		}

		return $result;
	}

	private function newQueryForIds(string $modelClass, array $ids): Builder
	{
		$modelPrototype = new $modelClass();

		return $modelPrototype
			->newModelQuery()
			->whereKey($ids);
	}
}
