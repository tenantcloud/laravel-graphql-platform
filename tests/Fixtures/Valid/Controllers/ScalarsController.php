<?php

namespace Tests\Fixtures\Valid\Controllers;

use Tests\Fixtures\Valid\Models\ScalarsData;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ScalarsController
{
	#[Query]
	public function scalars(ScalarsData $data): ScalarsData
	{
		return $data;
	}
}
