<?php

namespace Tests\Fixtures\TypeMappers;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException;

class AnyType extends ScalarType
{
    public string $name = 'Any';

    public function serialize(mixed $value): mixed
    {
        return $value;
    }

    public function parseValue(mixed $value): never
    {
        throw new GraphQLRuntimeException();
    }

    public function parseLiteral(Node $valueNode, array|null $variables = null): never
    {
        throw new GraphQLRuntimeException();
    }
}
