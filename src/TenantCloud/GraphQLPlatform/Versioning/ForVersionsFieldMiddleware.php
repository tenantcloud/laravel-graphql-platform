<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Support\Str;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class ForVersionsFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly string $currentVersion,
	) {
	}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$forVersionsAnnotations = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationsByType(ForVersions::class);

		if (!$forVersionsAnnotations) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$queryFieldDescriptor = $this->addAvailableComment($queryFieldDescriptor, $forVersionsAnnotations);

		$versionMatches = $this->compareVersions(
			$this->currentVersion,
			array_map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint, $forVersionsAnnotations)
		);

		return $versionMatches ?
			$fieldHandler->handle($queryFieldDescriptor) :
			null;
	}

	private function addAvailableComment(QueryFieldDescriptor $queryFieldDescriptor, array $forVersionsAnnotations): QueryFieldDescriptor
	{
		$comment = Str::of("Available in versions: ")
			->append(
				collect($forVersionsAnnotations)
					->map(fn (ForVersions $forVersionsAnnotation) => $forVersionsAnnotation->constraint)
					->implode(', ')
			)
			->append("\n")
			->append($queryFieldDescriptor->getComment() ?? '');

		return $queryFieldDescriptor->withComment($comment);
	}

	private const REGEX_VERSION_RULE = '([<>=]*)([\d.]*)';

	private function compareVersions(string $requestVersion, array $availableVersions): bool
	{
		foreach ($availableVersions as $rawVersionRule) {
			$versionRule = $this->parseRawVersion($rawVersionRule);

			if (version_compare($requestVersion, $versionRule['version'], $versionRule['operator'])) {
				return true;
			}
		}

		return false;
	}

	public function parseRawVersion(string $rawVersionRule): array
	{
		preg_match('/' . self::REGEX_VERSION_RULE . '/', $rawVersionRule, $result);

		if ($rawVersionRule !== $result[0]) {
			throw new \RuntimeException("Version {$rawVersionRule} didn't match " . self::REGEX_VERSION_RULE);
		}

		return [
			'operator' => $result[1],
			'version'  => $result[2],
		];
	}
}
