<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Exactly like the original ID type, but doesn't extend it so GraphQLite
 * doesn't attempt to cast it to their {@see ID} object that we don't want.
 */
class IdType extends ScalarType
{
	public string $name = 'ID';

	public ?string $description
		= 'The `ID` scalar type represents a unique identifier, often used to
refetch an object or as key for a cache. The ID type appears in a JSON
response as a String; however, it is not intended to be human-readable.
When expected as an input type, any string (such as `"4"`) or integer
(such as `4`) input value will be accepted as an ID.';

	public function serialize($value): string
	{
		$canCast = is_string($value) ||
			is_int($value) ||
			(is_object($value) && method_exists($value, '__toString'));

		if (!$canCast) {
			$notID = Utils::printSafe($value);

			throw new SerializationError("{$this->name} cannot represent a non-string and non-integer value: {$notID}");
		}

		return (string) $value;
	}

	public function parseValue($value): string
	{
		if (\is_string($value) || \is_int($value)) {
			return (string) $value;
		}

		throw new Error("{$this->name} cannot represent a non-string and non-integer value");
	}

	public function parseLiteral(Node $valueNode, array $variables = null): string
	{
		if ($valueNode instanceof StringValueNode || $valueNode instanceof IntValueNode) {
			return $valueNode->value;
		}

		$notID = Printer::doPrint($valueNode);

		throw new Error("{$this->name} cannot represent a non-string and non-integer value: {$notID}", $valueNode);
	}
}
