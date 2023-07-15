<?php

namespace Tests\Fixtures\Controllers;

use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use TenantCloud\GraphQLPlatform\Selection\InjectSelection;
use Tests\Fixtures\Models\SelectionResponse;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SelectionController
{
	#[Query]
	public function fullSelection(#[InjectSelection] array $selection): SelectionResponse
	{
		return new SelectionResponse(
			new LengthAwarePaginatorImpl([], 10, 10),
			$selection,
		);
	}

	#[Query]
	public function nestedSelection(#[InjectSelection('users.nodes')] array $selection): SelectionResponse
	{
		return new SelectionResponse(
			new LengthAwarePaginatorImpl([], 10, 10),
			$selection,
		);
	}
}
