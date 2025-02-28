<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\Carbon\CarbonRootTypeMapper;
use TenantCloud\GraphQLPlatform\Scalars\Carbon\DateTimeType;
use TenantCloud\GraphQLPlatform\Scalars\Carbon\DurationType;

#[CoversClass(CarbonRootTypeMapper::class)]
#[CoversClass(DateTimeType::class)]
#[CoversClass(DurationType::class)]
class CarbonTest extends IntegrationTestCase
{
	#[Test]
	public function returnsCarbonTypes(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						firstUser {
							somethingAfter
							createdAt
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'somethingAfter' => 'PT1H',
				'createdAt'      => '2020-01-03T00:00:00.000000Z',
			]);
	}

	#[Test]
	#[DataProvider('acceptsDateTimeTypeProvider')]
	public function acceptsDateTimeType(bool $valid, string $createdAt): void
	{
		$response = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($createdAt: DateTime!) {
						createUser(
							data: {
								name: "Alex",
								createdAt: $createdAt,
								somethingAfter: "PT1H",
							}
						) {
							createdAt
						}
					}
					GRAPHQL,
				['createdAt' => $createdAt]
			);

		if ($valid) {
			$response->assertSuccessful()
				->assertData([
					'createdAt' => '2020-01-03T12:00:00.000000Z',
				]);
		} else {
			$response->assertErrors([
				['message' => "Variable \"\$createdAt\" got invalid value \"{$createdAt}\"; Value is not a valid ISO formatted date time."],
			]);
		}
	}

	public static function acceptsDateTimeTypeProvider(): iterable
	{
		yield from [
			[true, '2020-01-03T12:00:00Z'],
			[true, '2020-01-03T12:00:00.000Z'],
			[true, '2020-01-03T12:00:00.000000Z'],
			[true, '2020-01-03T20:00:00+08:00'],
			[true, '2020-01-03T03:45:00-08:15'],
			[true, '2020-01-03T20:00:00.000000+08:00'],
			[true, '2020-01-03T03:45:00.000-08:15'],

			// Technically part of the ISO8601 spec, but are not commonly
			// supported and definitely are not supported by Carbon.
			[false, '2020-01-03T20:00:00+08'],
			[false, '2020-01-03T03:45:00-0815'],
			[false, '2020-01-03T20:00:00.000000+08'],
			[false, '2020-01-03T03:45:00.000-0815'],

			// Just plainly invalid. Not ISO8601
			[false, '2020-01-03T20:00:00'],
			[false, '2020-01-03 20:00:00'],
			[false, '2020-01-03 20:00:00Z'],
			[false, '2020-01-03'],
			[false, '2020-01-03T12:00:00.000000000Z'],
			[false, '2020-01-03T25:00:00Z'],
			[false, '2020-1-3T00:00:00Z'],
			[false, '2020/01/03T00:00:00Z'],
		];
	}

	#[Test]
	#[TestWith([null, '"2020-01-03T12:00:00.000000Z"'])]
	#[TestWith(['Value is not a valid ISO formatted date time.', '"2020-01-03"'])]
	#[TestWith(['DateTime cannot represent a non string value: 123', '123'])]
	#[TestWith(['DateTime cannot represent a non string value: []', '[]'])]
	#[TestWith(['DateTime cannot represent a non string value: CONSTANT', 'CONSTANT'])]
	public function acceptsDateTimeTypeAsLiteral(?string $error, string $createdAt): void
	{
		$response = $this
			->graphQL(
				<<<GRAPHQL
					mutation {
						createUser(
							data: {
								name: "Alex",
								createdAt: {$createdAt},
								somethingAfter: "PT1H",
							}
						) {
							createdAt
						}
					}
					GRAPHQL,
			);

		if (!$error) {
			$response->assertSuccessful()
				->assertData([
					'createdAt' => '2020-01-03T12:00:00.000000Z',
				]);
		} else {
			$response->assertErrors([
				['message' => $error],
			]);
		}
	}

	#[Test]
	#[DataProvider('acceptsDurationTypeProvider')]
	public function acceptsDurationType(bool $valid, string $somethingAfter): void
	{
		$response = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($somethingAfter: Duration!) {
						createUser(
							data: {
								name: "Alex",
								createdAt: "2020-01-03T12:00:00Z",
								somethingAfter: $somethingAfter,
							}
						) {
							somethingAfter
						}
					}
					GRAPHQL,
				['somethingAfter' => $somethingAfter]
			);

		if ($valid) {
			$response->assertSuccessful()
				->assertData([
					'somethingAfter' => 'P8DT13H23M34S',
				]);
		} else {
			$response->assertErrors([
				['message' => "Variable \"\$somethingAfter\" got invalid value \"{$somethingAfter}\"; Value is not a valid ISO formatted duration."],
			]);
		}
	}

	public static function acceptsDurationTypeProvider(): iterable
	{
		yield from [
			[true, 'P8DT13H23M34S'],
			[true, 'P1W1DT13H23M34S'],

			// Just plainly invalid. Not ISO8601
			[false, '1 week'],
			[false, '5 minutes'],
			[false, '100'],
		];
	}

	#[Test]
	#[TestWith([null, '"P8DT13H23M34S"'])]
	#[TestWith(['Value is not a valid ISO formatted duration.', '"SOME"'])]
	#[TestWith(['Duration cannot represent a non string value: 123', '123'])]
	#[TestWith(['Duration cannot represent a non string value: []', '[]'])]
	#[TestWith(['Duration cannot represent a non string value: CONSTANT', 'CONSTANT'])]
	public function acceptsDurationTypeAsLiteral(?string $error, string $somethingAfter): void
	{
		$response = $this
			->graphQL(
				<<<GRAPHQL
					mutation {
						createUser(
							data: {
								name: "Alex",
								createdAt: "2020-01-03T12:00:00.000000Z",
								somethingAfter: {$somethingAfter},
							}
						) {
							somethingAfter
						}
					}
					GRAPHQL,
			);

		if (!$error) {
			$response->assertSuccessful()
				->assertData([
					'somethingAfter' => 'P8DT13H23M34S',
				]);
		} else {
			$response->assertErrors([
				['message' => $error],
			]);
		}
	}
}
