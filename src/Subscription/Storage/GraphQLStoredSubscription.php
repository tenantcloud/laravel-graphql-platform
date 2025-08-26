<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use Closure;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Utils\AST;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\SerializableClosure\SerializableClosure;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;

/**
 * @property string                $id
 * @property bool                  $active
 * @property string                $channel
 * @property SubscriptionTransport $transport
 * @property string                $schema_name
 * @property DocumentNode          $document
 * @property array<string, mixed>  $variables
 * @property Closure|null          $resolve
 * @property Closure|null          $filter
 * @property CarbonImmutable|null  $expires_at
 */
class GraphQLStoredSubscription extends Model implements Subscription
{
	use HasUuids;

	protected $table = 'graphql_subscriptions';

	protected $casts = [
		'active'     => 'boolean',
		'variables'  => 'array',
		'expires_at' => 'immutable_datetime',
	];

	/**
	 * @return MorphTo<Authenticatable&Model, $this>
	 */
	public function owner(): MorphTo
	{
		/* @phpstan-ignore return.type */
		return $this->morphTo();
	}

	/**
	 * @return Attribute<SubscriptionTransport, SubscriptionTransport>
	 */
	public function transport(): Attribute
	{
		return new Attribute(
			get: fn (string $value) => resolve(SubscriptionTransportManager::class)->driver($value),
			set: fn (SubscriptionTransport $value) => $value->type(),
		);
	}

	/**
	 * @return Attribute<DocumentNode, DocumentNode>
	 */
	public function document(): Attribute
	{
		return new Attribute(
			get: fn (string $value) => AST::fromArray(json_decode($value, true, flags: JSON_THROW_ON_ERROR)),
			set: fn (DocumentNode $value) => json_encode($value, JSON_THROW_ON_ERROR),
		);
	}

	/**
	 * @return Attribute<Closure|null, Closure|null>
	 */
	public function resolve(): Attribute
	{
		return new Attribute(
			get: fn (?string $value) => $value ? unserialize($value)->getClosure() : null,
			set: fn (?Closure $value) => $value ? serialize(new SerializableClosure($value)) : null,
		);
	}

	/**
	 * @return Attribute<Closure|null, Closure|null>
	 */
	public function filter(): Attribute
	{
		return new Attribute(
			get: fn (?string $value) => $value ? unserialize($value)->getClosure() : null,
			set: fn (?Closure $value) => $value ? serialize(new SerializableClosure($value)) : null,
		);
	}
}
