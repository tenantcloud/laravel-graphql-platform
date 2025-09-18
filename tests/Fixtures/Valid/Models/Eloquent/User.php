<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Notifications\Notifiable;
use TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization\Authorize;
use Tests\Fixtures\Valid\Policies\UserPolicy;
use TheCodingMachine\GraphQLite\Annotations\MagicField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property \DateTimeImmutable|null $email_verified_at
 */
#[Type]
#[MagicField(name: 'id', outputType: 'ID!')]
#[MagicField(name: 'name', outputType: 'String!')]
#[MagicField(name: 'email', outputType: 'EmailAddress!', annotations: [new Authorize('view')])]
#[MagicField(name: 'emailVerifiedAt', outputType: 'DateTime', sourceName: 'email_verified_at', annotations: [new Authorize('view')])]
#[UsePolicy(UserPolicy::class)]
class User extends \Illuminate\Foundation\Auth\User
{
	use Notifiable;

	protected function casts(): array
	{
		return [
			'email_verified_at' => 'immutable_datetime',
		];
	}
}
