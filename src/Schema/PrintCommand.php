<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use GraphQL\Utils\SchemaPrinter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PrintCommand extends Command
{
	protected $signature = 'graphql:print {path} {--name= : Name of the schema from the registry}';

	protected $description = 'Prints the GraphQL schema into a file.';

	public function handle(SchemaRegistry $schemaRegistry, Filesystem $filesystem): int
	{
		$schemaName = $this->option('name') ?: SchemaRegistry::DEFAULT;

		if (!$schema = $schemaRegistry->get($schemaName)) {
			$this->error("Schema '{$schemaName}' is not registered.");

			return self::FAILURE;
		}

		$printed = SchemaPrinter::doPrint($schema);

		$filesystem->put(
			$this->normalizePath(base_path($this->argument('path'))),
			$printed,
		);

		return self::SUCCESS;
	}

	private function normalizePath(string $path): string
	{
		return array_reduce(
			explode('/', $path),
			fn (string $carry, string $part) => match ($part) {
				'', '.' => $carry,
				'..'    => dirname($carry),
				default => preg_replace('/\\/+/', '/', "{$carry}/{$part}"),
			},
			'/'
		);
	}
}
