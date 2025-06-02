<?php

namespace TenantCloud\GraphQLPlatform\Subscription\Storage;

use Carbon\CarbonImmutable;
use Closure;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Utils\AST;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\SerializableClosure\SerializableClosure;
use TenantCloud\GraphQLPlatform\Subscription\Subscription;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;

/**
 * @property string                $id
 * @property string                $channel
 * @property SubscriptionTransport $transport
 * @property string                $schema_name
 * @property DocumentNode          $document
 * @property array                 $variables
 * @property Closure|null          $resolve
 * @property Closure|null          $filter
 * @property CarbonImmutable|null  $expires_at
 */
class GraphQLStoredSubscription extends Model implements Subscription
{
	use HasUuids;

	protected $table = 'graphql_subscriptions';

	protected $casts = [
		'variables'  => 'array',
		'expires_at' => 'immutable_datetime',
	];

	public function transport(): Attribute
	{
		return new Attribute(
			get: fn (string $value) => resolve(SubscriptionTransportManager::class)->driver($value),
			set: fn (SubscriptionTransport $value) => $value->type(),
		);
	}

	public function document(): Attribute
	{
		return new Attribute(
			get: fn (string $value) => AST::fromArray(json_decode($value, true, flags: JSON_THROW_ON_ERROR)),
			set: fn (DocumentNode $value) => json_encode($value, JSON_THROW_ON_ERROR),
		);
	}

	public function resolve(): Attribute
	{
		return new Attribute(
			get: fn (?string $value) => $value ? unserialize($value)->getClosure() : null,
			set: fn (?Closure $value) => $value ? serialize(new SerializableClosure($value)) : null,
		);
	}

	public function filter(): Attribute
	{
		return new Attribute(
			get: fn (?string $value) => $value ? unserialize($value)->getClosure() : null,
			set: fn (?Closure $value) => $value ? serialize(new SerializableClosure($value)) : null,
		);
	}
}
