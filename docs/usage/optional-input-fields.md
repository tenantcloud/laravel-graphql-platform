# Optional input fields

Sometimes you want to know if a field was passed as an input, even if it matches the default value or is nullable. 
For example, given this schema and mutation:

```graphql
input UpdateSomethingInput {
	id: Int!
	name: String
	description: String
	url: String
}

type Mutation {
	updateSomething(data: UpdateSomethingInput!): Boolean
}

mutation {
	updateSomething(data: {
		id: 123,
		name: "Asd",
		description: null,
	})
}
```

You'd wanna know that the client sent only the `id`, `name` and `description` fields, but not the `url` field. 
Basically, this adds a way to check if the input data "has" the field. To do this in PHP, there's a special 
case value `MissingValue`. This is how you'd use it:

```php
#[Input]
readonly class UpdateSomethingInput {
	public function __construct(
		public int $id,
		public ?string $name,
		public string|null|MissingValue $description = MissingValue::INSTANCE,
		public string|null|MissingValue $description = MissingValue::INSTANCE,
	) {}
}

class DataController {
	#[Mutation]
	public function updateSomething(
		UpdateSomethingInput $data,
	): bool {
		assert($data->id === 123);
		assert($data->name === 'Asd');
		assert($data->description === null);
		assert($data->url === MissingValue::INSTANCE);
	}
}
```

Notice the union types and `MissingValue::INSTANCE` default values. This is what allows us to check if the value was
passed (even if it was `null`), or if the field was missing from the request.
