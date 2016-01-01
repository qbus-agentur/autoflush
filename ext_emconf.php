<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Qbus Cache',
    'description' => '',
    'category' => 'backend',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'author_company' => 'Qbus',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'beta',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '1.0.0',
    'constraints' => array(
        'depends' => array(
            'extbase' => '6.0',
            'fluid' => '6.0',
            'typo3' => '6.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);
