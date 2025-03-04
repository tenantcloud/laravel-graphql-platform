<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

class HexColorType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	/** @see https://github.com/Urigo/graphql-scalars/blob/master/src/scalars/HexColorCode.ts#L4C24-L4C75 */
	private const COLOR_REGEX = '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';

	public string $name = 'HexColor';

	public ?string $description = 'The `HexColor` scalar type represents a 3, 4, 6 or 8 letter HEX color code, conforming to [`<hex-color>` type of Mozilla CSS reference](https://developer.mozilla.org/en-US/docs/Web/CSS/hex-color), such as `f3af3a`.';

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

		if (!preg_match(self::COLOR_REGEX, $value)) {
			throw new Error("{$this->name} cannot represent a non HEX formatted color code");
		}

		return $value;
	}
}
