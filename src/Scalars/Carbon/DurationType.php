<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Carbon;

use Carbon\CarbonInterval;
use DateInterval;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException;

class DurationType extends ScalarType
{
	/** @see https://github.com/Urigo/graphql-scalars/blob/master/src/scalars/iso-date/Duration.ts#L12 */
	private const DURATION_REGEX = '/^(-|\+)?P(?!$)((-|\+)?\d+(?:(\.|,)\d+)?Y)?((-|\+)?\d+(?:(\.|,)\d+)?M)?((-|\+)?\d+(?:(\.|,)\d+)?W)?((-|\+)?\d+(?:(\.|,)\d+)?D)?(T(?=(-|\+)?\d)((-|\+)?\d+(?:(\.|,)\d+)?H)?((-|\+)?\d+(?:(\.|,)\d+)?M)?((-|\+)?\d+(?:(\.|,)\d+)?S)?)?$/';

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

		if (!preg_match(self::DURATION_REGEX, $value)) {
			throw new Error('Value is not a valid ISO formatted duration.');
		}

		return new CarbonInterval($value);
	}

	public function parseLiteral($valueNode, array $variables = null): string
	{
		if ($valueNode instanceof StringValueNode) {
			return $valueNode->value;
		}

		$notString = Printer::doPrint($valueNode);

		throw new Error("Duration cannot represent a non string value: {$notString}", $valueNode);
	}
}
