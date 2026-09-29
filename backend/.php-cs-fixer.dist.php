<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/config'])
    ->append([__DIR__.'/bin/console', __DIR__.'/public/index.php', __FILE__]);

return (new Config())
    ->setRules(['@PSR12' => true])
    ->setRiskyAllowed(false)
    ->setCacheFile(__DIR__.'/var/php-cs-fixer.cache')
    ->setFinder($finder);
