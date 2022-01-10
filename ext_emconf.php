<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "table".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = [
    'title' => 'Table Library',
    'description' => 'This contains a base class which you can use to make your extensions independant from any specific table. And it can be used to make multiple language support with a separate language overlay table.',
    'category' => 'misc',
    'version' => '0.10.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.99.99',
            'typo3' => '11.5.0-11.5.99',
            'div2007' => '1.13.0-0.0.0',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'typo3db_legacy' => '1.0.0-1.1.99',
        ],
    ],
];

