<?php

namespace Tests\Integration;

use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionDataSender;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionTransportChangedException;
use Tests\FakeSubscriptionTransport;
use Tests\Fixtures\Models\User as UserFixture;

class SubscriptionsTest extends IntegrationTestCase
{
	use UsePusherChannelConventions;

	#[Test]
	public function storesSubscriptionAndReturnsIt(): void
	{
		$auth = UserFactory::new()->make(['id' => 123]);

		$result = $this
			->actingAs($auth)
			->graphQL(
				<<<'GRAPHQL'
					subscription {
						newUser {
							name
						}
					}
					GRAPHQL,
			)
			->assertErrors([
				[
					'message' => 'Subscriptions require a different transport. See error extensions for details on how to continue with the subscription.',
					'extensions' => [
						'code' => SubscriptionTransportChangedException::CODE,
						'subscription' => [
							'transport' => [
								'type' => FakeSubscriptionTransport::TYPE,
								'channel' => $channel = "auth:123:users.new",
							]
						]
					]
				]
			]);

		self::assertCount(1, $result->errors);
		self::assertNotNull($originalException = $result->errors[0]->getPrevious());
		self::assertInstanceOf(SubscriptionTransportChangedException::class, $originalException);
		self::assertSame([
			'code' => SubscriptionTransportChangedException::CODE,
			'subscription' => [
				'transport' => [
					'type' => FakeSubscriptionTransport::TYPE,
					'channel' => $channel,
				]
			]
		], $originalException->getExtensions());

		$this->app->make(SubscriptionDataSender::class)->send(
			$originalException->subscription,
			new UserFixture('', CarbonImmutable::now())
		);
	}
}
