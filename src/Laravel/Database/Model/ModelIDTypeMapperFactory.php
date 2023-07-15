<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model;

use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

class ModelIDTypeMapperFactory implements RootTypeMapperFactoryInterface
{
	public function create(RootTypeMapperInterface $next, RootTypeMapperFactoryContext $context): RootTypeMapperInterface
	{
		return new ModelIDTypeMapper($next);
	}
}
