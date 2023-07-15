<?php

namespace Tests\Fixtures\Controllers;

use Psr\Http\Message\UploadedFileInterface;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class UploadController
{
	#[Mutation]
	public function uploadFile(UploadedFileInterface $file): string
	{
		return $file->getClientFilename();
	}
}
