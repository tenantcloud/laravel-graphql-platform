<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Concerns;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Printer;

/**
 * @property-read string $name
 */
trait ParsesAsString
{
	public function parseLiteral($valueNode, array $variables = null): string
	{
		if ($valueNode instanceof StringValueNode) {
			return $valueNode->value;
		}

		$notString = Printer::doPrint($valueNode);

		throw new Error("{$this->name} cannot represent a non string value: {$notString}", $valueNode);
	}
}
