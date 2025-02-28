<?php

namespace Tests\Unit\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Hamcrest\Matchers;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Schema\PrintCommand;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use Tests\TestCase;

#[CoversClass(PrintCommand::class)]
class PrintCommandTest extends TestCase
{
	#[Test]
	public function printsDefaultSchemaToAFile(): void
	{
		[$schema, $expected] = $this->fakeSchema();

		$schemaRegistry = $this->mock(SchemaRegistry::class);
		$schemaRegistry->expects()
			->names()
			->andReturn(['default']);
		$schemaRegistry->expects()
			->getOrFail('default')
			->andReturn($schema);

		$filesystem = $this->mock(Filesystem::class);
		$filesystem->expects()
			->ensureDirectoryExists(Matchers::endsWith('testbench-core/laravel'));
		$filesystem->expects()
			->put(Matchers::endsWith('/schema.gql'), $expected);

		$this
			->artisan(PrintCommand::class, [
				'path' => 'schema.gql',
			])
			->assertSuccessful();
	}

	#[Test]
	public function printsSpecifiedSchemaToAFile(): void
	{
		[$schema, $expected] = $this->fakeSchema();

		$schemaRegistry = $this->mock(SchemaRegistry::class);
		$schemaRegistry->expects()
			->names()
			->andReturn(['default', 'custom']);
		$schemaRegistry->expects()
			->getOrFail('custom')
			->andReturn($schema);

		$filesystem = $this->mock(Filesystem::class);
		$filesystem->expects()
			->ensureDirectoryExists(Matchers::endsWith('testbench-core/laravel'));
		$filesystem->expects()
			->put(Matchers::endsWith('/schema.gql'), $expected);

		$this
			->artisan(PrintCommand::class, [
				'path'   => 'schema.gql',
				'--name' => 'custom',
			])
			->assertSuccessful();
	}

	#[Test]
	public function printsAllSchemasToFiles(): void
	{
		[$schemaA, $expectedA] = $this->fakeSchema();
		[$schemaB, $expectedB] = $this->fakeSchema('b');

		$schemaRegistry = $this->mock(SchemaRegistry::class);
		$schemaRegistry->expects()
			->names()
			->andReturn(['a', 'b']);
		$schemaRegistry->expects()
			->getOrFail('a')
			->andReturn($schemaA);
		$schemaRegistry->expects()
			->getOrFail('b')
			->andReturn($schemaB);

		$filesystem = $this->mock(Filesystem::class);
		$filesystem->expects()
			->ensureDirectoryExists(Matchers::endsWith('testbench-core/laravel/base'))
			->times(2);
		$filesystem->expects()
			->put(Matchers::endsWith('base/a.graphql'), $expectedA);
		$filesystem->expects()
			->put(Matchers::endsWith('base/b.graphql'), $expectedB);

		$this
			->artisan(PrintCommand::class, [
				'path'  => 'base',
				'--all' => true,
			])
			->assertSuccessful();
	}

	private function fakeSchema(string $identifier = 'a'): array
	{
		return [
			new Schema([
				'query' => new ObjectType([
					'name'   => 'Type',
					'fields' => [
						$identifier => [
							'type' => Type::int(),
						],
					],
				]),
			]),
			<<<EOD
				schema {
				  query: Type
				}

				type Type {
				  {$identifier}: Int
				}

				EOD
		];
	}
}
