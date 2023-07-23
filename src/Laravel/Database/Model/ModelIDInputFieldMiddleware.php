<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model;

use Illuminate\Database\Eloquent\Model;
use ReflectionNamedType;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;

class ModelIDInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		$type = $inputFieldDescriptor->getRefProperty()->getType();

		if (!$type instanceof ReflectionNamedType || !is_a($type->getName(), Model::class, true)) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		/** @var ModelID|null $modelIDAnnotation */
		$modelIDAnnotation = $inputFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(ModelID::class);

		$inputFieldDescriptor = $inputFieldDescriptor->withResolver(function ($source, $id, ...$args) use ($modelIDAnnotation, $type, $inputFieldDescriptor) {
			$query = $type->getName()::query();

			if ($modelIDAnnotation?->lockForUpdate) {
				$query = $query->lockForUpdate();
			}

			$model = $query->find($id->val());

			return $inputFieldDescriptor->getResolver()($source, $model, ...$args);
		});

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}
}
