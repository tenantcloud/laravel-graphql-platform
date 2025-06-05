<?php

namespace Tests\Fixtures\Valid\Validation;

use Attribute;
use Symfony\Component\Validator\Constraints\Charset;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class PersonName extends Compound
{
	protected function getConstraints(array $options): array
	{
		return [
			new Length(min: 0, max: 32, options: $options),
			new Charset(['UTF-8']),
		];
	}
}
