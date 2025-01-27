# Versioning

Official GraphQL recommendation is not to use versioning at all, which basically means not to introduce breaking changes.
This is unrealistic and leads to weird field naming and a lot of unnecessary support. Which is why this library
provides support for actual versioning out of the box.

To do so, it uses [`laravel-api-versioning`](https://github.com/tenantcloud/laravel-api-versioning) package to handle
the basics: validate, extract version from the request and match it against the constraints for each specific field.
However, you'll still need to configure which versions should exist for a GraphQL schema specifically:

```php
class YourAppServiceProvider {
	public function register(): void {
    	$this->app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator, Application $app) => $configurator
				// ...
				->addSchema('latest', fn (SchemaConfigurator $configurator) => $configurator->forVersion('latest'))
				->addSchema('v1', fn (SchemaConfigurator $configurator) => $configurator->forVersion('1'))
				->addSchema('v2', fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
		);
	}
}
```

After which you can start adding `#[ForVersions]` attributes to controller, input and type fields:

```php
#[Input]
#[Type]
readonly class Data {
	public function __construct(
		#[Field]
		#[ForVersions('>=2')]
		public string $field,

		#[Field(name: 'field')]
		#[ForVersions('<=1')]
		public int $fieldV1,
	) {}
}

class DataController {
	#[Query]
	#[ForVersions('>=2')]
	public function onlyForV2(): void {}

	#[Query]
	#[ForVersions('<=1')]
	public function onlyForV1(): void {}
}
```

By using the `name` parameter of `#[Query]`, `#[Mutation]` and `#[Field]` attributes you can also create fields
with identical names in GraphQL schema, but different implementations in code. This is how the schema
above would look like for different versions:

`latest` or `v2`:

```graphql
type Data {
	field: String!
}
type DataInput {
	field: String!
}
type Query {
	onlyForV2: Void
}
```

`v1`:

```graphql
type Data {
	field: Int!
}
type DataInput {
	field: Int!
}
type Query {
	onlyForV1: Void
}
```
