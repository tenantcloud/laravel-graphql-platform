<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Notifications;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Schema\SchemaNotFoundException;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionChannels;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionDataSender;

/**
 * A Laravel notifications channel you should use to send new items into subscriptions.
 *
 * A notification must define a `toGraphQL($notifiable): GraphQLMessage` method for this to work.
 */
class GraphQLChannel
{
	public function __construct(
		private readonly SubscriptionStorage    $subscriptionStorage,
		private readonly SubscriptionDataSender $subscriptionDataSender,
	)
	{
	}

	public function send($notifiable, Notification $notification): GraphQLMessage
	{
		$message = $this->data($notifiable, $notification);
		$subscriptions = $this->subscriptionStorage->subscriptionsByOwnerChannels($this->notifiableChannels($notifiable, $message->channels));

		foreach ($subscriptions as $subscription) {
			$this->subscriptionDataSender->send($subscription, $message->root);
		}

		return $message;
	}

	protected function data(mixed $notifiable, Notification $notification): GraphQLMessage
	{
		if (!method_exists($notification, 'toGraphQL')) {
			throw new RuntimeException('Notification is missing toGraphQL method.');
		}

		return $notification->toGraphQL($notifiable);
	}

	private function notifiableChannels($notifiable, array $channels): array
	{
		return array_map(fn (string $channel) => SubscriptionChannels::private($notifiable, $channel), $channels);
	}
}

