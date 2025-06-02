<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use GraphQL\Type\Schema;
use Illuminate\Support\Arr;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

class SchemaRegistry
{
	/** @var array<string, Lazy<Schema>> */
	private array $schemas = [];

	/**
	 * @param callable(): SchemaConfigurator $defaultSchemaConfigurator
	 */
	public function __construct(
		private readonly SchemaFactory $schemaFactory,
		private readonly mixed $defaultSchemaConfigurator,
	) {}

	/**
	 * @return list<string>
	 */
	public function names(): array
	{
		return array_keys($this->schemas);
	}

	public function nameFor(Schema $schema): string
	{
		return collect($this->schemas)
			->filter(fn (Lazy $lazySchema) => $lazySchema->isInitialized() && $lazySchema->value() === $schema)
			->keys()
			->first();
	}

	public function first(): ?Schema
	{
		return $this->getOrFail(Arr::first($this->names()));
	}

	public function get(string $name): ?Schema
	{
		return ($this->schemas[$name] ?? null)?->value();
	}

	public function getOrFail(string $name): Schema
	{
		if (!isset($this->schemas[$name])) {
			throw new SchemaNotFoundException($name);
		}

		return $this->schemas[$name]->value();
	}

	public function register(string $name, callable|SchemaConfigurator $configurator): void
	{
		$this->schemas[$name] = lazy(function () use ($configurator) {
			$configurator = match (true) {
				$configurator instanceof SchemaConfigurator => $configurator,
				default                                     => $configurator(
					($this->defaultSchemaConfigurator)(),
				),
			};

			return $this->schemaFactory->create($configurator);
		});
	}
}
