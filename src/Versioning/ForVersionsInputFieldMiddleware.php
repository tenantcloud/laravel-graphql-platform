<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use Illuminate\Support\Str;
use TenantCloud\APIVersioning\Constraint\ConstraintChecker;
use TenantCloud\APIVersioning\Version\Version;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;

class ForVersionsInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function __construct(
		private readonly Version $currentVersion,
		private readonly ConstraintChecker $checker,
	) {}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		$forVersionsAnnotations = $inputFieldDescriptor->getMiddlewareAnnotations()->getAnnotationsByType(ForVersions::class);

		if (!$forVersionsAnnotations) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$inputFieldDescriptor = $this->addAvailableComment($inputFieldDescriptor, $forVersionsAnnotations);

		$versionMatches = $this->checker->compareVersions(
			$this->currentVersion,
			array_map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint, $forVersionsAnnotations)
		);

		return $versionMatches ?
			$inputFieldHandler->handle($inputFieldDescriptor) :
			null;
	}

	/**
	 * @param list<ForVersions> $forVersionsAnnotations
	 */
	private function addAvailableComment(InputFieldDescriptor $inputFieldDescriptor, array $forVersionsAnnotations): InputFieldDescriptor
	{
		$comment = Str::of('Available in versions: ')
			->append(
				collect($forVersionsAnnotations)
					->map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint)
					->implode(', ')
			)
			->append("\n")
			->append($inputFieldDescriptor->getComment() ?? '');

		return $inputFieldDescriptor->withComment($comment);
	}
}
