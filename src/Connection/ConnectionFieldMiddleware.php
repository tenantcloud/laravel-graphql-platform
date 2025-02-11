<?php

namespace TenantCloud\GraphQLPlatform\Connection;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;
use TheCodingMachine\GraphQLite\InvalidDocBlockRuntimeException;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\ServiceResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourcePropertyResolver;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameter;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use TheCodingMachine\GraphQLite\Reflection\DocBlock\DocBlockFactory;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use Webmozart\Assert\Assert;

class ConnectionFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly ConnectionTypeMapper $connectionTypeMapper,
		private readonly DocBlockFactory $docBlockFactory,
		private readonly ArgumentResolver $argumentResolver,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$originalResolver = $queryFieldDescriptor->getOriginalResolver();

		$reflector = match (true) {
			$originalResolver instanceof SourcePropertyResolver => $originalResolver->propertyReflection(),
			$originalResolver instanceof SourceMethodResolver   => $originalResolver->methodReflection(),
			$originalResolver instanceof ServiceResolver        => (function () use ($originalResolver) {
				$callable = $originalResolver->callable();

				return new ReflectionMethod($callable[0], $callable[1]);
			})(),
			default => null,
		};
		$type = $reflector instanceof ReflectionMethod ?
			$reflector->getReturnType() :
			$reflector?->getType();

		if (
			!$type instanceof ReflectionNamedType ||
			!in_array($type->getName(), [CursorConnectable::class, OffsetConnectable::class], true)
		) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$docBlock = $this->docBlockFactory->create($reflector);
		$phpDocType = $reflector instanceof ReflectionMethod ?
			$this->getDocBlocReturnType($docBlock, $reflector) :
			$this->getDocBlockPropertyType($docBlock, $reflector);

		Assert::isInstanceOfAny($phpDocType, [Object_::class, Collection::class], 'Connectable must specify a type using phpdoc: OffsetConnectable<Model>');

		$queryFieldDescriptor = match ($type->getName()) {
			CursorConnectable::class => $this->mapCursorConnectable(
				$queryFieldDescriptor,
				$phpDocType,
				$reflector,
				$docBlock,
			),
			OffsetConnectable::class => $this->mapOffsetConnectable(
				$queryFieldDescriptor,
				$phpDocType,
				$reflector,
				$docBlock,
			),
		};

		return $fieldHandler->handle($queryFieldDescriptor);
	}

	private function mapCursorConnectable(
		QueryFieldDescriptor $queryFieldDescriptor,
		Object_|Collection $type,
		ReflectionMethod|ReflectionProperty $reflector,
		DocBlock $docBlockObj
	): QueryFieldDescriptor {
		$field = $this->connectionTypeMapper->cursorConnectionField(
			$type,
			$reflector,
			$docBlockObj,
		);

		return $queryFieldDescriptor
			->withType($field->getType())
			->withParameters([
				...Arr::mapWithKeys($field->args, fn (Argument $arg) => [
					$arg->name => new InputTypeParameter(
						name: $arg->name,
						type: $arg->getType(),
						description: $arg->description,
						hasDefaultValue: $arg->defaultValueExists(),
						defaultValue: $arg->defaultValue,
						argumentResolver: $this->argumentResolver,
					),
				]),
				...$queryFieldDescriptor->getParameters(),
			])
			->withResolver(function (mixed $source, ...$args) use ($field, $queryFieldDescriptor) {
				$first = array_shift($args);
				$after = array_shift($args);
				$last = array_shift($args);
				$before = array_shift($args);

				$result = $queryFieldDescriptor->getResolver()($source, ...$args);

				Assert::isInstanceOf($result, CursorConnectable::class);

				/** @var CursorConnectable<mixed, CursorConnectionEdge<mixed>> $result */
				/* @phpstan-ignore-next-line */
				return ($field->resolveFn)($result, [
					'first'  => $first,
					'after'  => $after,
					'last'   => $last,
					'before' => $before,
				]);
			});
	}

	private function mapOffsetConnectable(
		QueryFieldDescriptor $queryFieldDescriptor,
		Object_|Collection $type,
		ReflectionMethod|ReflectionProperty $reflector,
		DocBlock $docBlockObj
	): QueryFieldDescriptor {
		$field = $this->connectionTypeMapper->offsetConnectionField(
			$type,
			$reflector,
			$docBlockObj,
		);

		return $queryFieldDescriptor
			->withType($field->getType())
			->withParameters([
				...Arr::mapWithKeys($field->args, fn (Argument $arg) => [
					$arg->name => new InputTypeParameter(
						name: $arg->name,
						type: $arg->getType(),
						description: $arg->description,
						hasDefaultValue: $arg->defaultValueExists(),
						defaultValue: $arg->defaultValue,
						argumentResolver: $this->argumentResolver,
					),
				]),
				...$queryFieldDescriptor->getParameters(),
			])
			->withResolver(function (mixed $source, ...$args) use ($field, $queryFieldDescriptor) {
				$limit = array_shift($args);
				$offset = array_shift($args);

				$result = $queryFieldDescriptor->getResolver()($source, ...$args);

				Assert::isInstanceOf($result, OffsetConnectable::class);

				/** @var OffsetConnectable<mixed, OffsetConnectionEdge<mixed>> $result */
				/* @phpstan-ignore-next-line */
				return ($field->resolveFn)($result, [
					'limit'  => $limit,
					'offset' => $offset,
				]);
			});
	}

	private function getDocBlocReturnType(DocBlock $docBlock, ReflectionMethod $refMethod): \phpDocumentor\Reflection\Type|null
	{
		/** @var array<int, Return_> $returnTypeTags */
		$returnTypeTags = $docBlock->getTagsByName('return');

		if (count($returnTypeTags) > 1) {
			throw InvalidDocBlockRuntimeException::tooManyReturnTags($refMethod);
		}
		$docBlockReturnType = null;

		if (isset($returnTypeTags[0])) {
			$docBlockReturnType = $returnTypeTags[0]->getType();
		}

		return $docBlockReturnType;
	}

	private function getDocBlockPropertyType(DocBlock $docBlock, ReflectionProperty $refProperty): \phpDocumentor\Reflection\Type|null
	{
		/** @var Var_[] $varTags */
		$varTags = $docBlock->getTagsByName('var');

		if (!$varTags) {
			return null;
		}

		if (count($varTags) > 1) {
			throw InvalidDocBlockRuntimeException::tooManyVarTags($refProperty);
		}

		return reset($varTags)->getType();
	}
}
