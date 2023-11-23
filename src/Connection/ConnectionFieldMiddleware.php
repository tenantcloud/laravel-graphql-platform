<?php

namespace TenantCloud\GraphQLPlatform\Connection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\InvalidDocBlockRuntimeException;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\MagicPropertyResolver;
use TheCodingMachine\GraphQLite\Middlewares\ServiceResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourcePropertyResolver;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameter;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use TheCodingMachine\GraphQLite\Reflection\CachedDocBlockFactory;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use Webmozart\Assert\Assert;

class ConnectionFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly ConnectionTypeMapper $connectionTypeMapper,
		private readonly CachedDocBlockFactory $cachedDocBlockFactory,
		private readonly ArgumentResolver $argumentResolver,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$originalResolver = $queryFieldDescriptor->getOriginalResolver();

		$reflector = match (true) {
			$originalResolver instanceof SourcePropertyResolver => $originalResolver->propertyReflection(),
			$originalResolver instanceof SourceMethodResolver => $originalResolver->methodReflection(),
			$originalResolver instanceof ServiceResolver => (function () use ($originalResolver) {
				$callable = $originalResolver->callable();

				return new ReflectionMethod($callable[0], $callable[1]);
			})(),
			default => null,
		};
		$type = $reflector instanceof ReflectionMethod ?
			$reflector?->getReturnType() :
			$reflector?->getType();

		if (
			!$type instanceof ReflectionNamedType ||
			!in_array($type->getName(), [CursorConnectable::class, OffsetConnectable::class], true)
		) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$docBlock = $this->cachedDocBlockFactory->getDocBlock($reflector);
		$phpDocType = $reflector instanceof ReflectionMethod ?
			$this->getDocBlocReturnType($docBlock, $reflector) :
			$this->getDocBlockPropertyType($docBlock, $reflector);
		assert($phpDocType !== null);

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
		\phpDocumentor\Reflection\Type $type,
		ReflectionMethod|ReflectionProperty $reflector,
		DocBlock $docBlockObj
	): QueryFieldDescriptor {
		$generics = PhpDocTypes::genericToTypes($type);

		return $queryFieldDescriptor
			->withType(
				$this->connectionTypeMapper->toGraphQLOutputType(
					PhpDocTypes::generic(CursorConnection::class, $generics),
					null,
					$reflector,
					$docBlockObj
				)
			)
			->withParameters([
				...$queryFieldDescriptor->getParameters(),
				'first' => new InputTypeParameter(
					name: 'first',
					type: Type::int(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
				'after' => new InputTypeParameter(
					name: 'after',
					type: Type::string(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
				'last' => new InputTypeParameter(
					name: 'last',
					type: Type::int(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
				'before' => new InputTypeParameter(
					name: 'before',
					type: Type::string(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
			])
			->withResolver(function (...$args) use ($queryFieldDescriptor) {
				$before = array_pop($args);
				$last = array_pop($args);
				$after = array_pop($args);
				$first = array_pop($args);

				$result = $queryFieldDescriptor->getResolver()(...$args);

				Assert::isInstanceOf($result, CursorConnectable::class);

				/** @var CursorConnectable $result */
				return $result->cursor($first, $after, $last, $before);
			});
	}

	private function mapOffsetConnectable(
		QueryFieldDescriptor $queryFieldDescriptor,
		\phpDocumentor\Reflection\Type $type,
		ReflectionMethod|ReflectionProperty $reflector,
		DocBlock $docBlockObj
	): QueryFieldDescriptor {
		$generics = PhpDocTypes::genericToTypes($type);

		return $queryFieldDescriptor
			->withType(
				$this->connectionTypeMapper->toGraphQLOutputType(
					PhpDocTypes::generic(OffsetConnection::class, $generics),
					null,
					$reflector,
					$docBlockObj
				)
			)
			->withParameters([
				...$queryFieldDescriptor->getParameters(),
				'offset' => new InputTypeParameter(
					name: 'offset',
					type: Type::int(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
				'limit' => new InputTypeParameter(
					name: 'limit',
					type: Type::int(),
					description: null,
					hasDefaultValue: true,
					defaultValue: null,
					argumentResolver: $this->argumentResolver,
				),
			])
			->withResolver(function (...$args) use ($queryFieldDescriptor) {
				$limit = array_pop($args);
				$offset = array_pop($args);

				$result = $queryFieldDescriptor->getResolver()(...$args);

				Assert::isInstanceOf($result, OffsetConnectable::class);

				/** @var OffsetConnectable $result */
				return $result->offset($limit, $offset);
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
