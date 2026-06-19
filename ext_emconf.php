<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Environment State Manager',
    'description' => 'Environment builder and state manager for TYPO3 CMS.',
    'version' => '1.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
