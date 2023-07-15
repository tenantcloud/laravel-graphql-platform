<?php

namespace TenantCloud\GraphQLPlatform\Validation\ConstraintDescription;

use Symfony\Component\Validator\Constraint;

interface ConstraintDescriptionProvider
{
	public function provide(Constraint $constraint): ?ConstraintDescription;
}
