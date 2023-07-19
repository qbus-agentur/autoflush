<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Automatic cache flush',
    'description' => 'Automatic cache flush for menus and levelmedia',
    'category' => 'backend',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'author_company' => 'Qbus Internetagentur GmbH',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'autoload' => [
        'psr-4' => ['Qbus\\Autoflush\\' => 'Classes'],
    ],
];
