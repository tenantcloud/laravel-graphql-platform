<?php

namespace TenantCloud\GraphQLPlatform\Connection\Cursor;

final class CursorConnectionPageInfo
{
	public function __construct(
		public readonly bool $hasNextPage,
		public readonly bool $hasPreviousPage,
		public readonly ?string $startCursor,
		public readonly ?string $endCursor,
	) {}

	public static function fromCursors(?string $startCursor, ?string $endCursor): self
	{
		return new self(
			$endCursor !== null,
			$startCursor !== null,
			$startCursor,
			$endCursor,
		);
	}
}
