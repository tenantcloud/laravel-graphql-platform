<?php

namespace Tests\Integration\Laravel;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Laravel\Database\Transactional;
use TenantCloud\GraphQLPlatform\Laravel\Database\TransactionalFieldMiddleware;
use Tests\Fixtures\Database\Factories\CommentFactory;
use Tests\Integration\IntegrationTestCase;

#[CoversClass(Transactional::class)]
#[CoversClass(TransactionalFieldMiddleware::class)]
class TransactionalTest extends IntegrationTestCase
{
	#[Test]
	public function beginsTransactionBeforeResolvingParameters(): void
	{
		// DB::enableQueryLog() does not track transaction queries, so we can't use that.
		// Event::fake() doesnt record all events in a single array, so we don't know the exact order
		$events = collect();

		Event::listen(fn (TransactionBeginning $event) => $events->push($event));
		Event::listen(fn (TransactionCommitted $event) => $events->push($event));
		Event::listen(fn (QueryExecuted $event) => $events->push($event));

		$comment = CommentFactory::new()
			->newPost()
			->newAuthor()
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($comment: ID!) {
						deleteComment(comment: $comment)
					}
					GRAPHQL,
				['comment' => $comment->id]
			)
			->assertSuccessful();

		$events = $events->filter(
			fn ($event) => $event instanceof TransactionBeginning ||
			$event instanceof TransactionCommitted ||
			($event instanceof QueryExecuted && str_contains($event->sql, 'select * from "comments" where "comments"."id" = ?')) ||
			($event instanceof QueryExecuted && str_contains($event->sql, 'delete from "comments" where "id" = ?'))
		);

		self::assertCount(4, $events);

		self::assertInstanceOf(TransactionBeginning::class, $events->shift());
		/* @phpstan-ignore-next-line */
		self::assertInstanceOf(QueryExecuted::class, $events->shift());
		/* @phpstan-ignore-next-line */
		self::assertInstanceOf(QueryExecuted::class, $events->shift());
		/* @phpstan-ignore-next-line */
		self::assertInstanceOf(TransactionCommitted::class, $events->shift());

		self::assertEmpty($events);
	}
}
