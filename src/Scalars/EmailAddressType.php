<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

class EmailAddressType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	/** @see https://github.com/Urigo/graphql-scalars/blob/master/src/scalars/EmailAddress.ts#L6C5-L6C140 */
	private const EMAIL_REGEX = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

	public string $name = 'EmailAddress';

	public ?string $description = 'The `EmailAddress` scalar type represents an email address, conforming to [`email address` part of the `HTML`](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address) standard.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function parseValue(mixed $value): string
	{
		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!preg_match(self::EMAIL_REGEX, $value)) {
			throw new Error("{$this->name} cannot represent a non email address");
		}

		return $value;
	}
}
