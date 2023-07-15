<?php

namespace TenantCloud\GraphQLPlatform\Selection;

use Attribute;
use RuntimeException;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotationInterface;

/**
 * Use this attribute to inject the selected fields as an array:
 *
 * ```graphql
 * {
 *     findSomething {
 *         foo
 *         bar {
 *             foobar
 *         }
 *     }
 * }
 * ```
 *
 * is injected as:
 *
 * ```php
 * [
 *     'foo' => true,
 *     'bar' => [
 *         'foobar' => true,
 *     ],
 * ]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectSelection implements ParameterAnnotationInterface
{
	/**
	 * @param string|null $prefix Optionally provide a prefix key to only return keys from that subselection
	 */
	public function __construct(
		public readonly ?string $prefix = null
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getTarget(): string
	{
		throw new RuntimeException();
	}
}
