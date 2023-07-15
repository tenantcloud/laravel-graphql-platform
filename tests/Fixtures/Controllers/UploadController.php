<?php

namespace Tests\Fixtures\Controllers;

use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use Psr\Http\Message\UploadedFileInterface;
use TenantCloud\GraphQLPlatform\Selection\InjectSelection;
use Tests\Fixtures\Models\SelectionResponse;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class UploadController
{
	#[Mutation]
	public function uploadFile(UploadedFileInterface $file): string
	{
		return $file->getClientFilename();
	}
}
