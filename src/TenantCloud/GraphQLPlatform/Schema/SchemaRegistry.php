<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use TheCodingMachine\GraphQLite\Schema;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class SchemaRegistry
{
	public const DEFAULT = 'default';

	/** @var array<string, Lazy<Schema>> */
	private array $schemas = [];

	/**
	 * @param callable(): SchemaConfigurator $defaultSchemaConfigurator
	 */
	public function __construct(
		private readonly SchemaFactory $schemaFactory,
		private readonly mixed $defaultSchemaConfigurator,
	)
	{
	}

	public function get(string $name): ?Schema
	{
		return ($this->schemas[$name] ?? null)?->value();
	}

	public function getOrFail(string $name): Schema
	{
		return $this->schemas[$name]->value();
	}

	public function register(string $name, callable|SchemaConfigurator $configurator): void
	{
		$this->schemas[$name] = lazy(function () use ($configurator) {
			$configurator = match (true) {
				$configurator instanceof SchemaConfigurator => $configurator,
				default => $configurator(
					($this->defaultSchemaConfigurator)()
				),
			};

			return $this->schemaFactory->create($configurator);
		});
	}
}
