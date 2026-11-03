<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\Plugin\SuitePlugins;
use Testo\Application\Config\SuiteConfig;
use Testo\Inline\InlineTestPlugin;

return new ApplicationConfig(
    src: ['src'],
    suites: [
        new SuiteConfig(
            name: 'Unit',
            location: ['tests/Unit'],
        ),
        new SuiteConfig(
            name: 'Arch',
            location: ['tests/Arch'],
        ),
        new SuiteConfig(
            name: 'Sources',
            location: ['src'],
            plugins: SuitePlugins::only(new InlineTestPlugin()),
        ),
    ],
);
