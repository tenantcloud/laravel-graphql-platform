<?php

namespace TenantCloud\GraphQLPlatform\Subscription;

use Closure;
use GraphQL\Language\AST\DocumentNode;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransport;

/**
 * @property-read string $id
 * @property-read string $channel
 * @property-read SubscriptionTransport $transport
 * @property-read string $schema_name
 * @property-read DocumentNode $document
 * @property-read array<string, mixed> $variables
 * @property-read Closure|null $resolve
 * @property-read Closure|null $filter
 */
interface Subscription {}
