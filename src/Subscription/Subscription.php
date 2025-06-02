<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use GraphQL\Language\AST\DocumentNode;
use Illuminate\Contracts\Auth\Authenticatable;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

/**
 * @property-read string $id
 * @property-read string $schema_name
 * @property-read SubscriptionTransport $transport
 * @property-read DocumentNode $document
 * @property-read array $variables
 * @property-read Closure|null $resolve
 * @property-read Closure|null $filter
 */
interface Subscription
{
}
