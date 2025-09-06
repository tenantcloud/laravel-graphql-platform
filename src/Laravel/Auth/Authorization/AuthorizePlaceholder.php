<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization;

enum AuthorizePlaceholder
{
	/**
	 * The entity in which the #[Authorize] attribute was defined. E.g. if a `class Blog`
	 * has a property with `#[Authorize('view', [AuthorizeEntity::THIS])]` attribute,
	 * we'll call the `$gate->authorize('view', $blog)` internally.
	 */
	case THIS;

	/**
	 * The value of a property or return value of the method where the #[Authorize] attribute was defined.
	 * E.g. if a `class Blog` has a property `$author` with `#[Authorize('view', [AuthorizeEntity::RETURN_VALUE])]`,
	 * we'll call the `$gate->authorize('view', $blog->author)` internally.
	 */
	case RESOLVED_VALUE;
}
