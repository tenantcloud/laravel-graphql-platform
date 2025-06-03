<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\Context\ContextToken;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Subscription\Storage\SubscriptionStorage;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;
use TheCodingMachine\GraphQLite\Annotations\Subscription;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\ServiceResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use Webmozart\Assert\Assert;

class SubscriptionFieldMiddleware implements FieldMiddlewareInterface
{
	/**
	 * @param ContextToken<string> $subscriptionTransportContextToken
	 */
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
		private readonly SubscriptionStorage $subscriptionStorage,
		private readonly ContextToken $subscriptionTransportContextToken,
		private readonly AuthenticationServiceInterface $authenticationService,
		private readonly SubscriptionTransportManager $subscriptionTransportManager,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
	{
		if (!$this->isChannelSubscription($queryFieldDescriptor)) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$queryFieldDescriptor = $this->replaceType($queryFieldDescriptor);

		$field = $fieldHandler->handle($queryFieldDescriptor);

		// Having a subscription and an item (a source) we need a way to convert the item to a GraphQL response payload.
		// E.g. if a subscription returns new users, we need to serialize a user using a `User` GraphQL type. However,
		// with the current Webonyx GraphQL internal implementation, this is not easily possible. We could get the output
		// type and try to convert it using that, but the implementation to do so is protected in Webonyx.
		$originalResolve = $field->resolveFn;

		$field->resolveFn = function (object|null $source, array $args, $context, ResolveInfo $info) use ($originalResolve) {
			// When a source (root) is passed, it means the subscription is already active
			// and this is just one of the items that should be resolved.
			if ($source instanceof SubscriptionRootContainer) {
				return with($source->root, $source->resolve);
			}

			$owner = $this->authenticationService->getUser();

			/** @var ChannelSubscription $channelSubscription */
			$channelSubscription = $originalResolve($source, $args, $context, $info);

			$transport = $this->chooseTransport($context);

			// This is a resolver for a single field from an operation, so passing in the entire operation looks
			// like a bug. However, GraphQL only allows a single field to be selected for a subscription operation,
			// so a subscription operation, in this case, is basically a field selection. This is great because
			// we won't have to do anything with the operation to cache it - instead we can just use ResolveInfo's
			// existing field and pass it in as-is to GraphQL's executeQuery().
			$subscription = $this->subscriptionStorage->subscribe(
				channelSubscription: $channelSubscription->withChannel(
					SubscriptionChannels::private($this->authenticationService->getUser(), $channelSubscription->channel)
				),
				transport: $transport,
				schemaName: $this->schemaRegistry->nameFor($info->schema),
				document: new DocumentNode([
					'definitions' => new NodeList([
						$info->operation,
						...array_values($info->fragments),
					]),
				]),
				variables: $info->variableValues,
				expiresAt: $transport->calculateExpiration(null),
			);

			// We can't return a value of type that doesn't match the output type of the subscription. E.g. if a
			// subscription produces User objects, if we try to return something else here, GraphQL will throw an
			// internal error which will cause a 500 Internal Server Error. Which is why instead of trying to fight
			// GraphQL implementation here, we're throwing an error which should be handled by the client as a
			// special case. It also makes it clear to the client that subscriptions aren't implemented
			throw new SubscriptionTransportChangedException($subscription);
		};

		return $field;
	}

	private function isChannelSubscription(QueryFieldDescriptor $descriptor): bool
	{
		$originalResolver = $descriptor->getOriginalResolver();

		$reflection = match (true) {
			$originalResolver instanceof SourceMethodResolver => $originalResolver->methodReflection(),
			$originalResolver instanceof ServiceResolver      => new ReflectionMethod(...$originalResolver->callable()),
			default                                           => null,
		};

		return $reflection?->getAttributes(Subscription::class) &&
			$reflection->getReturnType() instanceof ReflectionNamedType &&
			$reflection->getReturnType()->getName() === ChannelSubscription::class;
	}

	private function replaceType(QueryFieldDescriptor $queryFieldDescriptor): QueryFieldDescriptor
	{
		$type = $queryFieldDescriptor->getType();

		if (!$type instanceof NonNull || !$type->getWrappedType() instanceof ListOfType) {
			throw new RuntimeException("Subscription field {$queryFieldDescriptor->getName()} must define a return type annotation like so: @return ChannelSubscription<User>");
		}

		return $queryFieldDescriptor->withType(
			$type->getWrappedType()->getWrappedType()
		);
	}

	private function chooseTransport($context): SubscriptionTransport
	{
		Assert::isInstanceOf($context, Context::class);

		$transportName = $context->get($this->subscriptionTransportContextToken);

		return $this->subscriptionTransportManager->driver($transportName);
	}
}
