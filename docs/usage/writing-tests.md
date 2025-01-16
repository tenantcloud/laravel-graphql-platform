# Writing tests

Usually you'd be testing your APIs using the `->json()` family of methods
that a Laravel test case provides. These imitate an HTTP request and allow
basic assertions on the response. You could do the same with GraphQL,
but you shouldn't.

You shouldn't because GraphQL is [protocol agnostic](https://graphql.org/faq/general/#does-graphql-use-http).
It is usually served over HTTP, but it's not a hard requirement and it doesn't
actually use most common features of the HTTP protocol - verbs/methods, 
paths (endpoints), content types, status codes. Which is why tests shouldn't
be tied to the HTTP part either - in case there's ever a better alternative
that we can switch to, without rewriting the tests too.

To make your life simpler, the package provides `TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL`
trait with a single method:

```php
trait ExecutesGraphQL {
	protected function graphQL(
		string $query,
		array $variables = [],
		string|Schema $schema = null,
	): TestExecutionResult;
}
```

And you can use it like so:

```php
class UpdateTest extends TestCase
{
	use DatabaseTransactions;

	public function testUpdatesWebsite(): void
	{
		$this->actingAs($website->user)
			->updateWebsite([
				'website' => 123,
				'layout' => Layout::BASIC->value,
				'title' => 'Title',
			])
			->assertSuccessful()
			->assertData([
				'id' => 123,
				'layout' => Layout::BASIC->value,
				'title' => 'Title',
			]);

		$website->refresh();

		self::assertSame(Layout::BASIC, $website->layout);
		self::assertSame('Title', $website->title);
	}

	private function updateWebsite(array $data): TestExecutionResult
	{
		return $this->graphQL(
			<<<'GRAPHQL'
				mutation ($data: UpdateListingWebsiteInput!) { 
					updateListingWebsite(data: $data) { 
						id
						layout
						title
					} 
				}
				GRAPHQL,
			['data' => $data]
		);
	}
}
```

