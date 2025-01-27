# Field selection

Whenever a client makes a GraphQL request and requests fields in return,
you can use `#[InjectSelection]` attribute to inject the selected
fields as an argument to the method.

Given the following schema:

```graphql
type NestedData {
	field: String
	otherField: String
}

type Data {
	id: ID!
	nested: NestedData
	array: [NestedData!]!
}

type Query {
	data(id: Int!): Data!
}
```

You can inject the selection like this:

```php
class DataController {
	#[Query]
	public function data(
		int $id,
		#[InjectSelection] array $selection,
	): Data {
		assert($selection == [
			'id',
			'nested' => [
				'field',
			],
			'array' => [
				'otherField'
			],
		]);
	}
}
```

and receive the selection above for this query:

```graphql
query {
	data(id: 123) {
		id
		nested {
			field
		}
		array {
			otherField
		}
	}
}
```
