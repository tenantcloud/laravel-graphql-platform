<?php

require __DIR__ . '/vendor/kubawerlos/php-cs-fixer-custom-fixers/bootstrap.php';
require __DIR__ . '/vendor/tenantcloud/php-cs-fixer-rule-sets/bootstrap.php';

use PhpCsFixer\Finder;
use TenantCloud\PhpCsFixer\Config;

$finder = Finder::create()
	->in(__DIR__)
	->exclude('build')
	->exclude('tmp')
	->exclude('vendor')
	->exclude('tests/Fixtures/Valid/Models/Eloquent')
	->name('*.php')
	->notName('_*.php')
	->ignoreVCS(true);

return Config::make()->setFinder($finder);
