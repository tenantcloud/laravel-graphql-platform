<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Carbon;

use Carbon\CarbonInterval;
use DateInterval;
use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException;

class DurationType extends ScalarType
{
	public string $name = 'Duration';

	public ?string $description = 'The `Duration` scalar type represents a time duration conforming to the `ISO8601` standard, such as `P1W1DT13H23M34S`.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof DateInterval) {
			throw new InvariantViolation('Duration is not an instance of DateInterval: ' . Utils::printSafe($value));
		}

		return CarbonInterval::instance($value)->spec(true);
	}

	public function parseValue(mixed $value): CarbonInterval|null
	{
		if ($value === null) {
			return null;
		}

		if ($value instanceof DateInterval) {
			return CarbonInterval::instance($value);
		}

		if (!is_string($value)) {
			throw new GraphQLRuntimeException();
		}

		try {
			return new CarbonInterval($value);
		} catch (Exception $e) {
			throw new GraphQLRuntimeException(previous: $e);
		}
	}

	public function parseLiteral($valueNode, array|null $variables = null): string
	{
		if ($valueNode instanceof StringValueNode) {
			return $valueNode->value;
		}

		throw new GraphQLRuntimeException();
	}
}
