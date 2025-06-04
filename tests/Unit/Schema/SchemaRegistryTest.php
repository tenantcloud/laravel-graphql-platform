<?php

namespace Tests\Unit\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaFactory;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use Tests\TestCase;
use TheCodingMachine\GraphQLite\Schema;

#[CoversClass(SchemaRegistry::class)]
#[CoversClass(SchemaNotFoundException::class)]
class SchemaRegistryTest extends TestCase
{
	#[Test]
	public function managesSchemas(): void
	{
		$configurator1 = new SchemaConfigurator();
		$configurator2 = new SchemaConfigurator();

		$schemaFactory = mock(SchemaFactory::class);

		$registry = new SchemaRegistry(
			$schemaFactory,
			fn () => $configurator2,
		);

		self::assertEmpty($registry->names());

		$registry->register('v1', $configurator1);
		$registry->register('v2', fn (SchemaConfigurator $configurator) => $configurator);

		self::assertSame(['v1', 'v2'], $registry->names());

		// Register expectations later to make sure ->register() does not instantly resolve each schema
		$schemaFactory->expects()
			->create($configurator1)
			->andReturn($schema1 = mock(Schema::class));

		self::assertSame($schema1, $registry->first());
		self::assertSame($schema1, $registry->get('v1'));
		self::assertSame($schema1, $registry->getOrFail('v1'));
		self::assertSame('v1', $registry->nameFor($schema1));

		$schemaFactory->expects()
			->create($configurator2)
			->andReturn($schema2 = mock(Schema::class));

		self::assertSame($schema2, $registry->get('v2'));
		self::assertSame($schema2, $registry->getOrFail('v2'));
		self::assertSame('v2', $registry->nameFor($schema2));

		self::assertThrows(fn () => $registry->getOrFail('v3'), SchemaNotFoundException::class);
	}
}
