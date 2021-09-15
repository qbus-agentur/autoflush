<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Automatic cache flush',
    'description' => 'Automatic cache flush for menus and levelmedia',
    'category' => 'backend',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'author_company' => 'Qbus Internetagentur GmbH',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'version' => '2.1.1',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.7.0-10.4.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
    'autoload' => array(
        'psr-4' => array('Qbus\\Autoflush\\' => 'Classes')
    ),
);
