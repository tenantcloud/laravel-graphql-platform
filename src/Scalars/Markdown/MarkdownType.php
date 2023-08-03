<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Markdown;

use GraphQL\Type\Definition\StringType;

/**
 * Although Markdown cannot contain syntax errors or be otherwise invalid, so there's no validation to add,
 * it's still nice to have Markdown as a separate scalar type to let the client know that a specific
 * string field is not only formatted, but formatted using a specific spec of Markdown.
 */
class MarkdownType extends StringType
{
	public string $name = 'Markdown';

	public ?string $description = 'The `Markdown` scalar type represents a Markdown formatted text conforming to the `CommonMark` standard.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}
}
