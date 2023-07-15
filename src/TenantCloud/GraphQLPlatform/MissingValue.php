<?php

namespace TenantCloud\GraphQLPlatform;

/**
 * This is a special type that represents an absence of key-value pair in the payload.
 *
 * I.e. for this set of properties:
 *   public int $foo;
 *   public int|MissingValue $bar = MissingValue::INSTANCE;
 *
 * it would then be possible to differentiate between these two different payloads:
 *   {"foo": 123}
 *   {"foo": 123, "bar": 123}
 */
enum MissingValue
{
	/**
	 * This is a hack to allow setting the default value of properties to an instance of this type.
	 *
	 * It could have been a regular class with a static instance() method, but then it would be an error
	 * doing this: public int|MissingValue $bar = MissingValue::instance();
	 */
	case INSTANCE;
}
