<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Notifications;

use Illuminate\Notifications\Notification;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionChannels;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionEmitter;

/**
 * A Laravel notifications channel you should use to send new items into subscriptions.
 *
 * A notification must define a `toGraphQL($notifiable): GraphQLMessage` method for this to work.
 */
class GraphQLChannel
{
	public function __construct(
		private readonly SubscriptionEmitter $subscriptionEmitter,
	) {}

	public function send(mixed $notifiable, Notification $notification): GraphQLMessage
	{
		$message = $this->data($notifiable, $notification);

		$this->subscriptionEmitter->emit($this->notifiableChannels($notifiable, $message->channels), $message->root);

		return $message;
	}

	protected function data(mixed $notifiable, Notification $notification): GraphQLMessage
	{
		if (!method_exists($notification, 'toGraphQL')) {
			throw new RuntimeException('Notification is missing toGraphQL method.');
		}

		return $notification->toGraphQL($notifiable);
	}

	/**
	 * @param list<string> $channels
	 *
	 * @return list<string>
	 */
	private function notifiableChannels(mixed $notifiable, array $channels): array
	{
		return array_map(fn (string $channel) => SubscriptionChannels::private($notifiable, $channel), $channels);
	}
}
