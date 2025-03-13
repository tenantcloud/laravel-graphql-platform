<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

/**
 * Although Markdown cannot contain syntax errors or be otherwise invalid, so there's no validation to add,
 * it's still nice to have Markdown as a separate scalar type to let the client know that a specific
 * string field is not only formatted, but formatted using a specific spec of Markdown.
 */
class MarkdownType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	public string $name = 'Markdown';

	public ?string $description = 'The `Markdown` scalar type represents a Markdown formatted text, conforming to the [`CommonMark`](https://commonmark.org) standard.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function parseValue($value): string
	{
		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		// todo: additional validation?

		return $value;
	}
}
