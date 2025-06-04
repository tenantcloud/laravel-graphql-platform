<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

class PhoneNumberType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	/** @see https://ihateregex.io/expr/e164-phone */
	private const BASIC_PHONE_NUMBER_REGEX = '/^\+[1-9]\d{1,14}$/';

	public string $name = 'PhoneNumber';

	public ?string $description = 'The `PhoneNumber` scalar type represents an international phone number, conforming to [`E.164`](https://en.wikipedia.org/wiki/E.164) standard and Google\'s [`libphonenumber` `isValidNumber`](https://github.com/google/libphonenumber#quick-examples), such as `+41446681800`.';

	private static self $INSTANCE;

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct(
		private readonly PhoneNumberUtil $phoneNumberUtil,
		array $config = []
	) {
		parent::__construct($config);
	}

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self(phoneNumberUtil: PhoneNumberUtil::getInstance());
	}

	public function parseValue(mixed $value): string
	{
		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!preg_match(self::BASIC_PHONE_NUMBER_REGEX, $value)) {
			throw new Error("{$this->name} cannot represent a non E.164 formatted phone number");
		}

		try {
			$phoneNumber = $this->phoneNumberUtil->parse($value);
		} catch (NumberParseException $e) {
			throw new Error("{$this->name} cannot represent a non E.164 formatted phone number: {$e->getMessage()}");
		}

		if (!$this->phoneNumberUtil->isValidNumber($phoneNumber)) {
			throw new Error("{$this->name} cannot represent an invalid number according to Google's libphonenumber");
		}

		return $value;
	}
}
