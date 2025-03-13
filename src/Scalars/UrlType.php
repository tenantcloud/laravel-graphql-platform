<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;

class UrlType extends ScalarType
{
	use ParsesAsString;

	public string $name = 'Url';

	public ?string $description = 'The `Url` scalar type represents a valid URL, conforming to [`RFC3986`](https://www.ietf.org/rfc/rfc3986.txt) standard, such as `http://www.ietf.org/rfc/rfc2396.txt`.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof UriInterface) {
			throw new SerializationError("{$this->name} cannot represent a non UriInterface value: " . Utils::printSafe($value));
		}

		return (string) $value;
	}

	public function parseValue(mixed $value): UriInterface
	{
		if ($value instanceof UriInterface) {
			return Uri::new($value);
		}

		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		try {
			return Uri::new($value);
		} catch (SyntaxError $error) {
			throw new Error("{$this->name} cannot represent a non RFC3986 formatted value: {$error->getMessage()}");
		}
	}
}
