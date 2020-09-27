<?php

declare (strict_types = 1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\Configuration\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ]);
    $parameters->set(Option::SETS, [
        SetList::CLEAN_CODE,
        SetList::COMMENTS,
        SetList::COMMON,
        SetList::CONTROL_STRUCTURES,
        SetList::DEAD_CODE,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::PSR_12,
        SetList::SPACES,
        SetList::STRICT,
        // SetList::SYMFONY,//Don't enable as it formats the concat spaces
        // SetList::SYMFONY_RISKY,
        // SetList::SYMPLIFY,
        ]);
};
