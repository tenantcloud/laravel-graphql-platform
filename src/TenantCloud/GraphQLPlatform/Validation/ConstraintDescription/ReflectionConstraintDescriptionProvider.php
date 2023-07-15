<?php

namespace TenantCloud\GraphQLPlatform\Validation\ConstraintDescription;

use Illuminate\Support\Arr;
use ReflectionObject;
use ReflectionParameter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Compound;

class ReflectionConstraintDescriptionProvider implements ConstraintDescriptionProvider
{
	public function provide(Constraint $constraint): ?ConstraintDescription
	{
		$reflection = new ReflectionObject($constraint);

		$options = [];

		foreach ($reflection->getProperties() as $property) {
			if ($property->isStatic()) {
				continue;
			}

			if ($constraint instanceof Compound && $property->getName() === 'constraints') {
				continue;
			}

			if ($property->getName() === 'groups' || $property->getName() === 'options') {
				continue;
			}

			$value = $property->getValue($constraint);

			if ($property->hasDefaultValue() && $value == $property->getDefaultValue()) {
				continue;
			}

			/** @var ReflectionParameter $constructorParameter */
			$constructorParameter = Arr::first(
				$reflection->getConstructor()?->getParameters() ?? [],
				fn (ReflectionParameter $parameter) => $parameter->name === $property->getName(),
			);

			if ($constructorParameter && $constructorParameter->isDefaultValueAvailable() && $value === $constructorParameter->getDefaultValue()) {
				continue;
			}

			if ($value instanceof Constraint) {
				$value = $this->provide($value);
			} elseif (is_array($value)) {
				$value = array_map(fn ($value) => $value instanceof Constraint ? $this->provide($value) : $value, $value);
			}

			$options[$property->getName()] = $value;
		}

		return new ConstraintDescription($reflection->getShortName(), $options);
	}
}
