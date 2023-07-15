<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;

#[CoversClass(GraphQLPlatformServiceProvider::class)]
class ExploreTest extends IntegrationTestCase
{
	#[Test]
	public function returnsExplorePage(): void
	{
		$this->get('/graphql/explore')
			->assertOk()
			->assertViewIs(GraphQLPlatform::namespaced('explore'))
			->assertViewHas('endpoint', 'http://localhost/graphql');
	}
}
