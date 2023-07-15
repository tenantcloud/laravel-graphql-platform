<?php

namespace Tests\Integration\Http;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class UploadTest extends HttpIntegrationTestCase
{
	#[Test]
	public function uploadsFile(): void
	{
		// curl localhost:5000/graphql \
		//  -F operations='{ "query": "mutation ($file: Upload!) { uploadFile(file: $file) { success } }", "variables": { "file": null } }' \
		//  -F map='{ "0": ["variables.file"] }' \
		//  -F 0=@file.txt
		$this
			->multipartGraphQL(
				[
					'query' => <<<'GRAPHQL'
						mutation ($file: Upload!) {
							uploadFile(file: $file)
						}
						GRAPHQL,
					'variables' => [
						'file' => null,
					],
				],
				['0' => ['variables.file']],
				['0' => UploadedFile::fake()->create('test.pdf', 500)],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'uploadFile' => 'test.pdf',
				]
			]);
	}
}
