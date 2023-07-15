<?php

namespace Tests\Unit\Schema;

use GraphQL\Type\Schema;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Schema\PrintCommand;
use Tests\TestCase;

#[CoversClass(PrintCommand::class)]
class PrintCommandTest extends TestCase
{
	#[Test]
	public function printsSchemaToAFile(): void
	{
		$this->artisan(PrintCommand::class, [
			'path' => 'schema.gql',
		]);
	}
}
