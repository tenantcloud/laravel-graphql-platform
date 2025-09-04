<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;

class ModelIDParameter implements InputTypeParameterInterface
{
	public function __construct(
		private readonly InputTypeParameterInterface $delegate,
		/** @var class-string<Model> */
		private readonly string $modelClass,
		private readonly ?ModelID $modelIDAttribute,
	) {}

	public function resolve(?object $source, array $args, mixed $context, ResolveInfo $info): mixed
	{
		/** @var string|int|null $id */
		$id = $this->delegate->resolve($source, $args, $context, $info);

		if ($id === null) {
			return null;
		}

		$query = $this->modelClass::query();

		if ($this->modelIDAttribute?->lockForUpdate) {
			$query = $query->lockForUpdate();
		}

		return $query->findOrFail($id);
	}

	public function getType(): InputType&Type
	{
		return $this->delegate->getType();
	}

	public function hasDefaultValue(): bool
	{
		return $this->delegate->hasDefaultValue();
	}

	public function getDefaultValue(): mixed
	{
		return $this->delegate->getDefaultValue();
	}

	public function getName(): string
	{
		return $this->delegate->getName();
	}

	public function getDescription(): string
	{
		return $this->delegate->getDescription();
	}
}
