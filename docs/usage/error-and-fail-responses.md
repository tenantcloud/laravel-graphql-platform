# Error and fail responses

GraphQL does not standardize error responses - that is outside of some basic response structures defined by
the `graphql-over-http` spec, there's no guidance on how to deal with errors and failures in general.

### Failures as response union types

One option is to use a response union type:

```graphql
type Mutation {
	login(login: String!, password: String!): LoginResult
}

type LoginAuthenticated {
	bearerToken: String!
}
type LoginEmailNotConfirmedResult {
	email: String!
}
type LoginCodeTwoFactorRequired {
	temporaryToken: String!
}
type LoginEmailTwoFactorRequired {
	temporaryToken: String!
}

union LoginResult =
	| LoginAuthenticated
	| LoginEmailNotConfirmedResult
	| LoginCodeTwoFactorRequired
	| LoginEmailTwoFactorRequired
```

2xx response:

```json
{
	"data": {
		"login": {
			"__typename": "LoginCodeTwoFactorRequired",
			"temporaryToken": "123123123123"
		}
	}
}
```

This is the preferred way, as long as it falls under the following criteria:

-   not shared; used in one or a couple of places, but not everywhere (e.g. query/mutation specific ones)
-   client usually cannot predict those errors (e.g. by checking the information they already have)
-   client usually has to handle those errors in a specific way (e.g. not simply display an error message)
-   client usually has to differentiate between those errors, and treat each one as a separate flow

In this case, these errors are specific to the `login` mutation; the client cannot know ahead of time if the
login is going to require an email confirmation or 2FA; and the client will have to handle those errors
by redirecting the user to different pages, working with temporary token etc. Simply showing an error message
coming from the API is not an option here, as it would be a terrible user experience.

To do so in code, just use a regular union type:

```php
class Controller {
	public function login(): LoginAuthenticated | LoginEmailNotConfirmedResult | LoginCodeTwoFactorRequired | LoginEmailTwoFactorRequired {}
}
```

### Failures as untyped GraphQL errors

Another option is simply using the GraphQL spec's error responses:

```graphql
type Mutation {
	createProperty(data: CreatePropertyInput): Property
}

type Property {}

input CreatePropertyInput {
	name: String!
	address: String!
}
```

2xx response:

```json
{
	"errors": [
		{
			"message": "Some of the fields supplied contain validation errors.",
			"locations": [
				{
					"line": 1,
					"column": 2
				}
			],
			"extensions": {
				"code": "VALIDATION",
				"fields": {
					"name": ["May not contain more than 255 characters."]
				}
			}
		}
	]
}
```

There are still many things that could go wrong here: authentication, authorization, validation, throttling and
possibly many others. But notice all of those are not specific to this mutation; and that it's very likely
that all of these will not be handled on a granular level when calling this specific mutation. Rather,
clients would usually handle those on a global level:

-   authentication errors will get a new token on HTTP client middleware level;
-   authorization errors can usually be predicted by clients - either through doing their own checks, or by
    checking some kind of `rules` response key from previous requests to see if they can perform an action. But
    even if they don't do that, it's very unlikely that the client can do something about the error, other than
    to display an error message from the backend and let the user deal with it
-   validation errors are usually automatically mapped to inputs provided by the user
-   throttling just requires letting the user know they have to wait a bit

In all of the cases, the client usually either has a global handling logic for the error, or cannot do anything about it.

````

### Failures as non-GraphQL errors

The third option is to ignore the GraphQL spec altogether. This really is only necessary in cases where the backend or
any of it's proxies cannot handle the error themselves, so the best they can do is return a `5xx` code and let
the client know of the catastrophic failure :)

5xx response:

```json
{
	"message": "Ooops! Something went wrong",
	"__debug": {
		"trace": ["Possibly a trace here"]
	}
}
````
