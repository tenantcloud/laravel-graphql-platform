<?php

namespace Tests\Fixtures\Valid\Controllers;

use GraphQL\Error\UserError;
use RuntimeException;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ErrorController
{
	#[Query]
	public function clientSafeError(): int
	{
		throw new UserError('You did something wrong :(');
	}

	#[Query]
	public function clientUnsafeError(): int
	{
		throw new RuntimeException('We did something wrong.');
	}
}
