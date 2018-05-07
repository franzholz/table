<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "table".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = array(
    'title' => 'Table Library',
    'description' => 'This containts a base class which you can use to make your extensions independant from any specific table. And it can be used to make multiple language support with a separate language overlay table.',
    'category' => 'misc',
    'shy' => 0,
    'version' => '0.7.0',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '6.1.0-8.99.99',
            'div2007' => '1.10.1-0.0.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);

