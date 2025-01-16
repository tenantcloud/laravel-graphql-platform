# Input validation

Most of the time you want to validate your inputs. With Laravel applications,
the most common way of doing that is through `FormRequest` or `Validator`
classes, which contain the validation rules. After the validation, you get
an associative array of validated data, which you can then feed into a DTO,
for example.

However, this kind of process is error-prone and requires a lot of boilerplate.
To make it easier, GraphQLite automatically maps input data into DTOs:
- first, GraphQL validates the input data against the schema, making sure
all of the required fields were passed and that their types match
- second, GraphQLite constructs a DTO from that data
- last, Symfony Validator is triggered, which goes through all class properties
and validates each one separately using custom rules specified with attributes

All you have to do is add [Symfony Validator constraints](https://symfony.com/doc/current/validation.html#supported-constraints)
in a form of attributes and you're good:

```php
#[Input]
#[Cascade]
class UpdateDTO
{
	public function __construct(
		#[Field]
		#[When(
			condition: 'customizationNotAllowed',
			constraints: [new EqualTo(Layout::BASIC)]
		)]
		public Layout|MissingValue $layout = MissingValue::INSTANCE,
		
		#[Field]
		#[Length(min: 1, max: 500)]
		public string|MissingValue|null $title = MissingValue::INSTANCE,
		
		#[Field]
		#[Length(min: 1, max: 10000, normalizer: [HtmlNormalizer::class, 'withoutTags'])]
		public string|MissingValue|null $description = MissingValue::INSTANCE,
		
		#[Field]
		#[Count(max: 50)]
		#[Unique]
		/** @var array<string>|MissingValue */
		public mixed $teamMembers = MissingValue::INSTANCE,
	) {}

	public function customizationNotAllowed(): bool
	{
		return random_int(0, 1) === 1;
	}
}
```
