<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use Sokil\IsoCodes\Database\Currencies;
use Sokil\IsoCodes\IsoCodesFactory;
use Sokil\IsoCodes\TranslationDriver\DummyDriver;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;

class CurrencyType extends ScalarType
{
	use ParsesAsString;
	use SerializesAsParses;

	public string $name = 'Currency';

	public ?string $description = 'The `Currency` scalar type represents a three-letter currency code, conforming to [`ISO 4217`](https://en.wikipedia.org/wiki/ISO_4217) standard, such as `UAH`.';

	private static self $INSTANCE;

	public function __construct(
		private readonly Currencies $currencies,
		array $config = [],
	) {
		parent::__construct($config);
	}

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self(currencies: (new IsoCodesFactory(translationDriver: new DummyDriver()))->getCurrencies());
	}

	public function parseValue(mixed $value): string
	{
		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!$this->currencies->getByLetterCode($value)) {
			throw new Error("{$this->name} cannot represent a non ISO formatted currency code");
		}

		return $value;
	}
}
