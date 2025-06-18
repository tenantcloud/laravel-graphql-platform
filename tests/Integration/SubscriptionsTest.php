<?php

namespace Tests\Integration;

use Carbon\CarbonImmutable;
use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Subscription\ChannelSubscription;
use TenantCloud\GraphQLPlatform\Subscription\Notifications\GraphQLChannel;
use TenantCloud\GraphQLPlatform\Subscription\Notifications\GraphQLMessage;
use TenantCloud\GraphQLPlatform\Subscription\Storage\DatabaseSubscriptionStorage;
use TenantCloud\GraphQLPlatform\Subscription\Storage\GraphQLStoredSubscription;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionChannels;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionDataEmittedEvent;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionEmitter;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionFieldMiddleware;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionManager;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionRootContainer;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionTransportChangedException;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;
use TenantCloud\GraphQLPlatform\Subscription\UpkeepSubscriptionsCommand;
use TenantCloud\GraphQLPlatform\Testing\FakeSubscriptionTransport;
use Tests\Fixtures\Valid\Models\User as UserFixture;

#[CoversClass(GraphQLChannel::class)]
#[CoversClass(GraphQLMessage::class)]
#[CoversClass(DatabaseSubscriptionStorage::class)]
#[CoversClass(GraphQLStoredSubscription::class)]
#[CoversClass(ChannelSubscription::class)]
#[CoversClass(SubscriptionChannels::class)]
#[CoversClass(SubscriptionDataEmittedEvent::class)]
#[CoversClass(SubscriptionEmitter::class)]
#[CoversClass(SubscriptionFieldMiddleware::class)]
#[CoversClass(SubscriptionManager::class)]
#[CoversClass(SubscriptionRootContainer::class)]
#[CoversClass(SubscriptionTransportChangedException::class)]
#[CoversClass(UpkeepSubscriptionsCommand::class)]
#[CoversClass(GraphQLPlatform::class)]
class SubscriptionsTest extends IntegrationTestCase
{
	#[Test]
	public function storesSubscriptionAndReturnsIt(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$result = $this
			->httpGraphQL(
				<<<'GRAPHQL'
					subscription {
						newUser {
							name
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertJson([
				'errors' => [
					[
						'message'    => 'Subscriptions require a different transport. See error extensions for details on how to continue with the subscription.',
						'extensions' => [
							'code'         => SubscriptionTransportChangedException::CODE,
							'subscription' => [
								'transport' => [
									'type'    => FakeSubscriptionTransport::TYPE,
									'channel' => $channel = 'auth:123:users.new',
								],
							],
						],
					],
				],
			]);
	}

	#[Test]
	public function emitsDataToASubscription(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'                => 'test',
				'calculateExpiration' => null,
				'clientDetails'       => [],
			]);
		$subscriptionTransport->expects()
			->emit(Mockery::any(), [
				'data' => [
					'newUser' => [
						'name' => 'Alex',
					],
				],
			]);

		$subscription = $this->createSubscription($subscriptionTransport);

		$this->app->make(Guard::class)->forgetUser();

		$this->app->make(SubscriptionEmitter::class)->emitForSubscription(
			$subscription,
			new UserFixture('Alex', CarbonImmutable::now())
		);
	}

	#[Test]
	public function sendsDataToASubscriptionFromNotificationChannel(): void
	{
		$this->actingAs($auth = UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'                => 'test',
				'calculateExpiration' => null,
				'clientDetails'       => [],
			]);
		$subscriptionTransport->expects()
			->emit(Mockery::any(), [
				'data' => [
					'newUser' => [
						'name' => 'Alex',
					],
				],
			]);

		$this->createSubscription($subscriptionTransport);

		$this->app->make(Guard::class)->forgetUser();

		Notification::send($auth, new class () extends \Illuminate\Notifications\Notification {
			public function via(): array
			{
				return [GraphQLChannel::class];
			}

			public function toGraphQL(): GraphQLMessage
			{
				return (new GraphQLMessage(new UserFixture('Alex', CarbonImmutable::now())))
					->on('users.new');
			}
		});
	}

	#[Test]
	public function deactivatesSubscriptionWhenSchemaNoLongerExists(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'                => 'test',
				'calculateExpiration' => null,
				'clientDetails'       => [],
			]);
		$subscriptionTransport->expects()
			->deactivated(Mockery::any(), Mockery::type(SchemaNotFoundException::class));

		$subscription = $this->createSubscription($subscriptionTransport);

		self::assertInstanceOf(GraphQLStoredSubscription::class, $subscription);

		$subscription->schema_name = 'non_existent';
		$subscription->save();

		$this->app->make(Guard::class)->forgetUser();

		$this->app->make(SubscriptionEmitter::class)->emitForSubscription(
			$subscription,
			new UserFixture('Alex', CarbonImmutable::now())
		);

		self::assertModelExists($subscription);

		$subscription->refresh();

		self::assertFalse($subscription->active);
	}

	#[Test]
	public function deactivatesSubscriptionOnMalformedRequestError(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'                => 'test',
				'calculateExpiration' => null,
				'clientDetails'       => [],
			]);
		$subscriptionTransport->expects()
			->deactivated(Mockery::any(), Mockery::type(Error::class));

		$subscription = $this->createSubscription($subscriptionTransport);

		self::assertInstanceOf(GraphQLStoredSubscription::class, $subscription);

		$subscription->document = Parser::parse('subscription { nonExistentField }');
		$subscription->save();

		$this->app->make(Guard::class)->forgetUser();

		$this->app->make(SubscriptionEmitter::class)->emitForSubscription(
			$subscription,
			new UserFixture('Alex', CarbonImmutable::now())
		);

		self::assertModelExists($subscription);

		$subscription->refresh();

		self::assertFalse($subscription->active);
	}

	#[Test]
	public function upkeepCancelsExpiredSubscriptions(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'                => 'test',
				'calculateExpiration' => now()->subDay()->toImmutable(),
				'clientDetails'       => [],
			]);

		$subscription = $this->createSubscription($subscriptionTransport);

		self::assertInstanceOf(GraphQLStoredSubscription::class, $subscription);

		$this->app->make(Guard::class)->forgetUser();

		$this
			->artisan(UpkeepSubscriptionsCommand::class)
			->assertSuccessful();

		self::assertModelMissing($subscription);
	}

	#[Test]
	public function upkeepProlongsExpiringSubscriptions(): void
	{
		$this->actingAs(UserFactory::new()->make(['id' => 123]));

		$subscriptionTransport = mock(SubscriptionTransport::class)
			->allows([
				'type'          => 'test',
				'clientDetails' => [],
			]);
		$subscriptionTransport->expects()
			->calculateExpiration(null)
			->andReturn(now()->subDay()->toImmutable());
		$subscriptionTransport->expects()
			->calculateExpiration(Mockery::type(Subscription::class))
			->andReturn($prolongedUntil = now()->addDay()->toImmutable());

		$subscription = $this->createSubscription($subscriptionTransport);

		self::assertInstanceOf(GraphQLStoredSubscription::class, $subscription);

		$this->app->make(Guard::class)->forgetUser();

		$this
			->artisan(UpkeepSubscriptionsCommand::class)
			->assertSuccessful();

		self::assertModelExists($subscription);

		$subscription->refresh();

		self::assertSame($prolongedUntil->toDateTimeString(), $subscription->expires_at->toDateTimeString());
	}

	private function createSubscription(SubscriptionTransport $transport): Subscription
	{
		$this->app->make(SubscriptionTransportManager::class)->extend($transport->type(), fn () => $transport);

		$result = $this->app->make(GraphQLPlatform::class)
			->executeQuery(
				schema: $this->app->make(SchemaRegistry::class)->first(),
				source: <<<'GRAPHQL'
					subscription {
						newUser {
							name
						}
					}
					GRAPHQL,
				applyContext: function (Context $context) use ($transport): Context {
					$context->set($this->app->make(GraphQLPlatformServiceProvider::SUBSCRIPTION_TRANSPORT_CONTEXT_TOKEN), $transport->type());

					return $context;
				}
			);

		self::assertCount(1, $result->errors);
		self::assertNotNull($originalException = $result->errors[0]->getPrevious());
		self::assertInstanceOf(SubscriptionTransportChangedException::class, $originalException);

		return $originalException->subscription;
	}
}
