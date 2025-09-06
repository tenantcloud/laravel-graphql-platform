<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization;

use Closure;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Arr;
use TenantCloud\GraphQLPlatform\MissingValue;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use Webmozart\Assert\Assert;

class AuthorizeFieldMiddleware implements FieldMiddlewareInterface
{
	/**
	 * @param Closure(): Gate $resolveGate
	 */
	public function __construct(
		private readonly Closure $resolveGate,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$authorizeAttribute = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(Authorize::class);

		if (!$authorizeAttribute) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$authorizeBeforeResolving = $this->canAuthorizeWithoutResolving($authorizeAttribute);

		$queryFieldDescriptor = $queryFieldDescriptor->withResolver(function (mixed $source, ...$args) use ($authorizeAttribute, $authorizeBeforeResolving, $queryFieldDescriptor) {
			$executionSource = $queryFieldDescriptor->getOriginalResolver()->executionSource($source);

			if ($authorizeBeforeResolving) {
				$this->authorize($authorizeAttribute, $executionSource);
			}

			$resolved = $queryFieldDescriptor->getResolver()($source, ...$args);

			if ($authorizeBeforeResolving) {
				return $resolved;
			}

			if ($resolved instanceof Closure) {
				$resolved = new Deferred($resolved);
			}

			if ($resolved instanceof SyncPromise) {
				return $resolved->then(function (mixed $resolved) use ($executionSource, $authorizeAttribute) {
					$this->authorize($authorizeAttribute, $executionSource, $resolved);

					return $resolved;
				});
			}

			$this->authorize($authorizeAttribute, $executionSource, $resolved);

			return $resolved;
		});

		return $fieldHandler->handle($queryFieldDescriptor);
	}

	/**
	 * As an optimization, we can avoid resolving the field (in case it requires loading something from the database, for example)
	 * if the resolved value isn't used during authorization. This way we can throw the exception early.
	 */
	private function canAuthorizeWithoutResolving(Authorize $authorizeAttribute): bool
	{
		$requiresResolvedValue = Arr::first($authorizeAttribute->arguments, fn (mixed $arg) => $arg === AuthorizePlaceholder::RESOLVED_VALUE);

		return !$requiresResolvedValue;
	}

	private function authorize(Authorize $authorizeAttribute, mixed $executionSource, mixed $resolved = MissingValue::INSTANCE): void
	{
		$arguments = array_map(function (mixed $arg) use ($resolved, $executionSource) {
			if ($arg === AuthorizePlaceholder::RESOLVED_VALUE) {
				Assert::notSame($resolved, MissingValue::INSTANCE);
			}

			return match ($arg) {
				AuthorizePlaceholder::THIS           => $executionSource,
				AuthorizePlaceholder::RESOLVED_VALUE => $resolved,
				default                              => $arg,
			};
		}, $authorizeAttribute->arguments);

		$gate = ($this->resolveGate)();
		$gate->authorize($authorizeAttribute->ability, $arguments);
	}
}
