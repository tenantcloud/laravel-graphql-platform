<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use Carbon\CarbonInterval;
use DateInterval;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;

class DurationType extends ScalarType
{
	use ParsesAsString;

	/** @see https://github.com/Urigo/graphql-scalars/blob/master/src/scalars/iso-date/Duration.ts#L12 */
	private const DURATION_REGEX = '/^(-|\+)?P(?!$)((-|\+)?\d+(?:(\.|,)\d+)?Y)?((-|\+)?\d+(?:(\.|,)\d+)?M)?((-|\+)?\d+(?:(\.|,)\d+)?W)?((-|\+)?\d+(?:(\.|,)\d+)?D)?(T(?=(-|\+)?\d)((-|\+)?\d+(?:(\.|,)\d+)?H)?((-|\+)?\d+(?:(\.|,)\d+)?M)?((-|\+)?\d+(?:(\.|,)\d+)?S)?)?$/';

	public string $name = 'Duration';

	public ?string $description = 'The `Duration` scalar type represents a time duration conforming to the [`ISO-8601`](https://en.wikipedia.org/wiki/ISO_8601#Durations) standard, such as `P1W1DT13H23M34S`.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof DateInterval) {
			throw new SerializationError("{$this->name} cannot represent a non DateInterval value: " . Utils::printSafe($value));
		}

		return CarbonInterval::instance($value)->spec(true);
	}

	public function parseValue(mixed $value): CarbonInterval
	{
		if ($value instanceof DateInterval) {
			return CarbonInterval::instance($value);
		}

		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!preg_match(self::DURATION_REGEX, $value)) {
			throw new Error("{$this->name} cannot represent a non ISO formatted duration");
		}

		return new CarbonInterval($value);
	}
}
