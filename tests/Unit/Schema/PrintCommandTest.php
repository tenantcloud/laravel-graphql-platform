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
	public function printsSchemaToAFile(): void
	{
		$schemaRegistry = $this->mock(SchemaRegistry::class);
		$schemaRegistry->expects()
			->get(SchemaRegistry::DEFAULT)
			->andReturn(new Schema([
				'query' => new ObjectType([
					'name'   => 'Type',
					'fields' => [
						'a' => [
							'type' => Type::int(),
						],
					],
				]),
			]));

		$filesystem = $this->mock(Filesystem::class);
		$filesystem->expects()
			->put(
				Matchers::endsWith('/schema.gql'),
				<<<'GRAPHQL'
					schema {
					  query: Type
					}

					type Type {
					  a: Int
					}

					GRAPHQL
			);

		$this
			->artisan(PrintCommand::class, [
				'path' => 'schema.gql',
			])
			->assertSuccessful();
	}
}
