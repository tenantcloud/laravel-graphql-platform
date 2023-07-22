<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Support\Str;
use TenantCloud\APIVersioning\Constraint\ConstraintChecker;
use TenantCloud\APIVersioning\Version\Version;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class ForVersionsFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly Version $currentVersion,
		private readonly ConstraintChecker $checker,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$forVersionsAnnotations = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationsByType(ForVersions::class);

		if (!$forVersionsAnnotations) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$queryFieldDescriptor = $this->addAvailableComment($queryFieldDescriptor, $forVersionsAnnotations);

		$versionMatches = $this->checker->compareVersions(
			$this->currentVersion,
			array_map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint, $forVersionsAnnotations)
		);

		return $versionMatches ?
			$fieldHandler->handle($queryFieldDescriptor) :
			null;
	}

	private function addAvailableComment(QueryFieldDescriptor $queryFieldDescriptor, array $forVersionsAnnotations): QueryFieldDescriptor
	{
		$comment = Str::of('Available in versions: ')
			->append(
				collect($forVersionsAnnotations)
					->map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint)
					->implode(', ')
			)
			->append("\n")
			->append($queryFieldDescriptor->getComment() ?? '');

		return $queryFieldDescriptor->withComment($comment);
	}
}
