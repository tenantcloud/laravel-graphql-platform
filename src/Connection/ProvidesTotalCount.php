<?php

namespace TenantCloud\GraphQLPlatform\Connection;

interface ProvidesTotalCount
{
	public function totalCount(): int;
}
