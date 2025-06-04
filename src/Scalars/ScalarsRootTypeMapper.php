<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use League\Uri\Contracts\UriInterface;
use League\Uri\Uri;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TenantCloud\GraphQLPlatform\Scalars\Hints\CountryCode;
use TenantCloud\GraphQLPlatform\Scalars\Hints\Currency;
use TenantCloud\GraphQLPlatform\Scalars\Hints\Date;
use TenantCloud\GraphQLPlatform\Scalars\Hints\EmailAddress;
use TenantCloud\GraphQLPlatform\Scalars\Hints\HexColor;
use TenantCloud\GraphQLPlatform\Scalars\Hints\ID;
use TenantCloud\GraphQLPlatform\Scalars\Hints\Markdown;
use TenantCloud\GraphQLPlatform\Scalars\Hints\PhoneNumber;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

/**
 * Maps all of the additional scalar types.
 */
class ScalarsRootTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
	) {}

	public function toGraphQLOutputType(Type $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&GraphQLType
	{
		return $this->mapType($type, ScalarMappingHelpers::reflector($reflector)) ?? $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	public function toGraphQLInputType(Type $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&GraphQLType
	{
		return $this->mapType($type, ScalarMappingHelpers::reflector($reflector, $argumentName)) ?? $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return match ($typeName) {
			CountryCodeType::instance()->name     => CountryCodeType::instance(),
			CurrencyType::instance()->name        => CurrencyType::instance(),
			DateTimeType::instance()->name        => DateTimeType::instance(),
			DateType::instance()->name            => DateType::instance(),
			DurationType::instance()->name        => DurationType::instance(),
			EmailAddressType::instance()->name    => EmailAddressType::instance(),
			GraphQLDocumentType::instance()->name => GraphQLDocumentType::instance(),
			HexColorType::instance()->name        => HexColorType::instance(),
			GraphQLType::id()->name               => GraphQLType::id(),
			MarkdownType::instance()->name        => MarkdownType::instance(),
			PhoneNumberType::instance()->name     => PhoneNumberType::instance(),
			UrlType::instance()->name             => UrlType::instance(),
			default                               => $this->next->mapNameToType($typeName),
		};
	}

	/**
	 * @return (OutputType&InputType&GraphQLType)|null
	 */
	private function mapType(Type $type, ReflectionMethod|ReflectionProperty|ReflectionParameter $reflector): ?GraphQLType
	{
		if (!$type instanceof Object_ && !$type instanceof String_) {
			return null;
		}

		if ($type instanceof String_) {
			if ($reflector->getAttributes(CountryCode::class)) {
				return CountryCodeType::instance();
			}

			if ($reflector->getAttributes(Currency::class)) {
				return CurrencyType::instance();
			}

			if ($reflector->getAttributes(EmailAddress::class)) {
				return EmailAddressType::instance();
			}

			if ($reflector->getAttributes(HexColor::class)) {
				return HexColorType::instance();
			}

			if ($reflector->getAttributes(ID::class)) {
				return GraphQLType::id();
			}

			if ($reflector->getAttributes(Markdown::class)) {
				return MarkdownType::instance();
			}

			if ($reflector->getAttributes(PhoneNumber::class)) {
				return PhoneNumberType::instance();
			}

			return null;
		}

		return match (PhpDocTypes::className($type)) {
			DateTimeInterface::class, DateTimeImmutable::class, CarbonImmutable::class => (fn () => $reflector->getAttributes(Date::class) ?
					DateType::instance() :
					DateTimeType::instance())(),
			DateInterval::class, CarbonInterval::class => DurationType::instance(),
			DocumentNode::class => GraphQLDocumentType::instance(),
			UriInterface::class, Uri::class => UrlType::instance(),

			default => null,
		};
	}
}
