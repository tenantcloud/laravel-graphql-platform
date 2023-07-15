# GraphQL platform

<p align="center">
    <a href="https://packagist.org/packages/tenantcloud/laravel-graphql-platform" title="Latest Unstable Version">
        <img src="https://poser.pugx.org/tenantcloud/laravel-graphql-platform/v/unstable" alt="Latest Unstable Version" />
    </a>
    <a href="https://packagist.org/packages/tenantcloud/laravel-graphql-platform" title="Total Downloads">
        <img src="https://poser.pugx.org/tenantcloud/laravel-graphql-platform/downloads" alt="Total Downloads" />
    </a>
    <a href="https://packagist.org/packages/tenantcloud/laravel-graphql-platform" title="License">
        <img src="https://poser.pugx.org/tenantcloud/laravel-graphql-platform/license" alt="License" />
    </a>
    <a href="https://github.com/tenantcloud/laravel-graphql-platform/actions" title="Continuous Integration">
        <img src="https://github.com/tenantcloud/laravel-graphql-platform/workflows/tests.yml/badge.svg" alt="Continuous Integration" />
    </a>
    <a href="https://codecov.io/gh/tenantcloud/laravel-graphql-platform" title="Code Coverage">
        <img src="https://codecov.io/gh/tenantcloud/laravel-graphql-platform/branch/master/graph/badge.svg" alt="Code Coverage" />
    </a>
</p>

Laravel GraphQL platform makes it simple to use utilities to develop a GraphQL API based
on `thecodingmachine/graphqlite` and `webonyx/graphql-php`. Out of the box, it provides
the following extensions for `graphqlite`:

- ✅ Complies with official [GraphQL-over-HTTP spec](https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#sec-Response)
- ✅ Laravel integration (debug mode, error handling etc)
- ✅ Testing tools & assertions
- ✅ Multiple schemas support
- ✅ `DateTime` and `Duration` scalar types as per [ISO8601](https://en.wikipedia.org/wiki/ISO_8601)
- ✅ Optional input fields
- ✅ Apollo [embeddable sandbox](https://www.apollographql.com/docs/graphos/explorer) page
- ✅ Input validation using [Symfony validator](https://symfony.com/doc/current/validation.html)
- ✅ Automatic persisted queries ([Apollo spec](https://www.apollographql.com/docs/apollo-server/performance/apq))
- ✅ Query complexity with custom cost per field ([Hot chocolate](https://chillicream.com/docs/hotchocolate/v13/security/operation-complexity))
- ✅ File uploads compliant with [spec](https://github.com/jaydenseric/graphql-multipart-request-spec)
- 🚧 Offset pagination (Relay spec-like)
- 🚧 Cursor pagination ([Relay spec](https://github.com/facebook/relay/blob/main/website/spec/Connections.md))
- 🚧 Subscriptions
- 🚧 Apollo tracing
