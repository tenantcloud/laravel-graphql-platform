<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

interface ProvidesTotalCount
{
	public function totalCount(): int;
}
