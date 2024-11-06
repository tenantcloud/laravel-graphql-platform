<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use GraphQL\Utils\SchemaPrinter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class PrintCommand extends Command
{
	protected $signature = 'graphql:print {path} {--all : Print all schemas} {--name= : Name of the schema from the registry}';

	protected $description = 'Prints the GraphQL schema into a file.';

	public function handle(SchemaRegistry $schemaRegistry, Filesystem $filesystem): int
	{
		$basePath = $this->normalizePath(base_path($this->argument('path')));

		[$schemaNames, $all] = $this->schemaNames($schemaRegistry);

		foreach ($schemaNames as $schemaName) {
			$path = $all ? "$basePath/$schemaName.graphql" : $basePath;
			$printed = SchemaPrinter::doPrint($schemaRegistry->getOrFail($schemaName));

			$filesystem->put($path, $printed);

			$this->info("Printed schema [$schemaName] to [$path]");
		}

		return self::SUCCESS;
	}

	/**
	 * @return array{ list<string>, bool }
	 */
	private function schemaNames(SchemaRegistry $schemaRegistry): array
	{
		$names = $schemaRegistry->names();

		if ($this->option('all')) {
			return [$names, true];
		}

		$name = $this->option('name') ?: Arr::first($names);

		Assert::inArray($name, $names);

		return [[$name], false];
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
