# Subscriptions

### Backend usage

To define a subscription, you use a `#[Subscription]` attribute. The return type must
be `ChannelSubscription`, and it's generic parameter must specify the output type of the
subscription. It may accept parameters, just like queries and mutations, apply additional
attributes, middlewares. It's also a good idea to check authorization:

```php
class ThreadController {
	use AuthorizesRequests;

	/** @return ChannelSubscription<Message> */
	#[Subscription]
	public function newThreadMessage(
		#[InjectUser] User $auth,
		#[ModelID] Thread $thread
	): ChannelSubscription {
		$this->authorizeForUser($auth, 'view', $thread);

		return new ChannelSubscription("threads.{$thread->id}.messages");
	}
}
```

As you can see, all we did is some checks - ones you would usually do when defining a
[Laravel broadcast channel](https://laravel.com/docs/11.x/broadcasting#example-application-authorizing-channels).
Then the method returns a `ChannelSubscription` with a channel name. That channel name
is what we would then use to publish events for that subscription through
[Laravel notifications](https://laravel.com/docs/11.x/notifications#custom-channels):

```php
readonly class NewThreadMessageNotification
{
	public function __construct(
		public Message $message,
	) {}

	public function via(): array
	{
		return [
			GraphQLChannel::class,
			// MailChannel::class,
		];
	}

	public function toGraphQL(): GraphQLMessage
	{
		return (new GraphQLMessage($this->message))
			->on("threads.{$this->message->thread->id}.messages");
	}
}
```

### Client usage

Other GraphQL implementations usually implement subscriptions directly, by adding a
SSE endpoint or a built-in websocket server. However, to be able to handle many thousands
of connections and keep them alive (which is generally a requirement for subscriptions),
we'd need some way of async execution - which, sadly, isn't possible in Laravel at the moment.

Instead, we'll be delegating the SSE/WS part to an external server, one that is completely
separate from our backend. This way, we could use a different technology that is more fit
to the task. The same thing was done in [graphql-ruby](https://graphql-ruby.org/subscriptions/pusher_implementation)
due to the limitations that Ruby has, similar to PHP. They've done a good job describing
how it works, so you should go and read their docs.

In our case, we're delegating the keep-alive connections part to Laravel broadcasting mechanism.
When you execute a `subscription` operation on the backend, instead of switching protocols
or keeping the connection open, like GraphQL servers usually do, it closes the connection
with a specific error code: `SUBSCRIPTION_REDIRECTED`. The error also includes
a channel name that the client should use to subscribe to that channel on the
broadcasting side, as well as an authorization signature so save a request to
`/broadcasting/auth`. The subscription will expire within 5 minutes if client
doesn't subscribe.
