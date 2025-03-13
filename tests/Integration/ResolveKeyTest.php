<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKey;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKeyParameter;
use TenantCloud\GraphQLPlatform\Resolve\ResolveKeyParameterMiddleware;

#[CoversClass(ResolveKey::class)]
#[CoversClass(ResolveKeyParameter::class)]
#[CoversClass(ResolveKeyParameterMiddleware::class)]
class ResolveKeyTest extends IntegrationTestCase
{
	#[Test]
	public function injectsResolveKey(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query ($arg2: NestedInput!) {
						resolveKey(arg1: true, arg2: $arg2)
					}
					GRAPHQL,
				['arg2' => ['name' => 'asd']]
			)
			->assertSuccessful()
			->assertData([
				'type'  => 'Query',
				'field' => 'resolveKey',
				'args'  => [
					'arg1' => true,
					'arg2' => [
						'name' => 'asd',
					],
				],
			]);
	}
}
