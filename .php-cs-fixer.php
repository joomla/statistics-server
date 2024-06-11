<?php

$finder = PhpCsFixer\Finder::create()
	->in(
		[
			__DIR__ . '/',
			__DIR__ . '/bin',
			__DIR__ . '/src',
			__DIR__ . '/tests',
			__DIR__ . '/www',
		]
	);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setHideProgress(false)
    ->setUsingCache(false)
    ->setRules(
        [
            // Basic ruleset is PSR 12
            '@PSR12'                         => true,
            // Short array syntax
            'array_syntax'                   => ['syntax' => 'short'],
            // List of values separated by a comma is contained on a single line should not have a trailing comma like [$foo, $bar,] = ...
            'no_trailing_comma_in_singleline' => true,
            // Arrays on multiline should have a trailing comma
            'trailing_comma_in_multiline'    => ['elements' => ['arrays']],
            // Align elements in multiline array and variable declarations on new lines below each other
            'binary_operator_spaces'         => ['operators' => ['=>' => 'align_single_space_minimal', '=' => 'align']],
            // The "No break" comment in switch statements
            'no_break_comment'               => ['comment_text' => 'No break'],
            // Remove unused imports
            'no_unused_imports'              => true,
            // Classes from the global namespace should not be imported
            'global_namespace_import'        => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
            // Alpha order imports
            'ordered_imports'                => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
            // There should not be useless else cases
            'no_useless_else'                => true,
            // Native function invocation
            'native_function_invocation'     => ['include' => ['@compiler_optimized']],
        ]
    )
    ->setFinder($finder);

return $config;
