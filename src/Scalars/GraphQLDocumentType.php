<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;

class GraphQLDocumentType extends ScalarType
{
	use ParsesAsString;

	public string $name = 'GraphQLDocument';

	public ?string $description = 'The `GraphQLDocument` scalar type represents a GraphQL document, like `query { me { id } }`.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof DocumentNode) {
			throw new SerializationError("{$this->name} cannot represent a non DocumentNode value: " . Utils::printSafe($value));
		}

		return Printer::doPrint($value);
	}

	public function parseValue(mixed $value): DocumentNode
	{
		if ($value instanceof DocumentNode) {
			return $value;
		}

		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		try {
			return Parser::parse(new Source($value, 'GraphQL'));
		} catch (SyntaxError $exception) {
			throw new Error("{$this->name} cannot represent non-GraphQL document: {$exception}");
		}
	}
}
