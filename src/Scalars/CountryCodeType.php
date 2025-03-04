<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use Sokil\IsoCodes\Database\Countries;
use Sokil\IsoCodes\IsoCodesFactory;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

class CountryCodeType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	public string $name = 'CountryCode';

	public ?string $description = 'The `CountryCode` scalar type represents a two-letter country code, conforming to [`ISO 3166-1 alpha-2`](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) standard, such as `UA`.';

	private static self $INSTANCE;

	public function __construct(
		private readonly Countries $countries,
		array $config = [],
	) {
		parent::__construct($config);
	}

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self(countries: (new IsoCodesFactory())->getCountries());
	}

	public function parseValue(mixed $value): string
	{
		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!$this->countries->getByAlpha2($value)) {
			throw new Error("{$this->name} cannot represent a non ISO formatted country code");
		}

		return $value;
	}
}
